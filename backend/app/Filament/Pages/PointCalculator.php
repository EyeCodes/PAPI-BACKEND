<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use App\Models\Company;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyProgramRule;
use App\Models\CustomerPoint;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Testing\Fluent\Concerns\Has;
use App\Traits\HasRoleHelpers;

class PointCalculator extends Page implements HasForms
{
    use InteractsWithForms;
    use HasRoleHelpers;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.pages.point-calculator';

    protected static ?string $navigationGroup = 'Loyalty Program Tools';

    protected static ?string $title = 'Point Calculator & Customer Manager';

    public ?array $data = [];

    // Earning calculation
    public ?string $calculatedPoints = null;
    public ?string $qrCodePath = null;
    public ?array $ruleBreakdown = [];

    // Customer search
    public ?int $customerBalance = null;
    public ?array $customerTransactions = [];

    // Redemption
    public ?string $redemptionQrPath = null;

    public function mount(): void
    {
        $user = Auth::user();
        $availableCompanies = $this->getAvailableCompanies();
        
        // Pre-fill with appropriate company based on user role
        $defaultCompanyId = null;
        
        if ($this->isSuperAdmin($user)) {
            // For superadmin, use first available company
            $defaultCompanyId = $availableCompanies->first()?->id;
        } elseif ($this->isHandler($user)) {
            // For handler, use their first company
            $defaultCompanyId = $user->companies->first()?->id;
        }

        $this->form->fill([
            'company_id' => $defaultCompanyId,
            'search_company_id' => $defaultCompanyId,
            'redeem_company_id' => $defaultCompanyId,
        ]);
    }

    /**
     * Check if user is a superadmin
     */
    private function isSuperAdmin($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->roles()->where('name', 'superadmin')->exists();
    }

    /**
     * Check if user is a handler
     */
    private function isHandler($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->roles()->where('name', 'handler')->exists();
    }

    /**
     * Get companies available to the current user based on their role
     */
    private function getAvailableCompanies()
    {
        $user = Auth::user();
        
        if ($this->isSuperAdmin($user)) {
            // Superadmin can see all active companies
            return Company::where('is_active', true)->get();
        }
        
        if ($this->isHandler($user)) {
            // Handler can only see their assigned companies
            return $user->companies()->where('is_active', true)->get();
        }
        
        // Default: no companies for unrecognized roles
        return collect();
    }

    /**
     * Get company options for select fields based on user permissions
     */
    private function getCompanyOptions()
    {
        return $this->getAvailableCompanies()->pluck('company_name', 'id')->toArray();
    }

    /**
     * Check if user can access a specific company
     */
    private function canAccessCompany($companyId): bool
    {
        $user = Auth::user();
        
        if ($this->isSuperAdmin($user)) {
            return true;
        }
        
        if ($this->isHandler($user)) {
            return $user->companies()->where('companies.id', $companyId)->exists();
        }
        
        return false;
    }

    public function form(Form $form): Form
    {
        $user = Auth::user();
        $companyOptions = $this->getCompanyOptions();
        $isHandler = $this->isHandler($user);
        
        return $form
            ->schema([
                Tabs::make('calculator_tabs')
                    ->activeTab(1)
                    ->tabs([
                        Tabs\Tab::make('earn_points')
                            ->label('Earn Points')
                            ->icon('heroicon-o-plus-circle')
                            ->schema([
                                Section::make('Select Company & Program')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Company')
                                            ->options($companyOptions)
                                            ->required()
                                            ->live()
                                            ->disabled($isHandler && count($companyOptions) === 1) // Disable if handler has only one company
                                            ->helperText($isHandler ? 'You can only manage loyalty programs for your assigned companies' : null)
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // Validate access to selected company
                                                if ($state && !$this->canAccessCompany($state)) {
                                                    Notification::make()
                                                        ->title('Access Denied')
                                                        ->body('You do not have access to this company')
                                                        ->danger()
                                                        ->send();
                                                    $set('company_id', null);
                                                    return;
                                                }
                                                
                                                $set('loyalty_program_id', null);
                                                $this->resetCalculation();
                                            }),

                                        Select::make('loyalty_program_id')
                                            ->label('Loyalty Program')
                                            ->options(function (Get $get) {
                                                $companyId = $get('company_id');
                                                if (!$companyId || !$this->canAccessCompany($companyId)) {
                                                    return [];
                                                }

                                                return LoyaltyProgram::where('company_id', $companyId)
                                                    ->whereHas('rules', function($query) {
                                                        $query->where('is_active', true);
                                                    })
                                                    ->where('is_active', true)
                                                    ->pluck('program_name', 'id');
                                            })
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function () {
                                                $this->resetCalculation();
                                            })
                                            ->disabled(fn (Get $get): bool => !$get('company_id'))
                                            ->helperText('Only programs with active rules are shown'),
                                    ])
                                    ->columns(2),

                                Section::make('Purchase Details')
                                    ->schema([
                                        TextInput::make('purchase_amount')
                                            ->label('Purchase Amount')
                                            ->prefix('PHP')
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->step(0.01)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function () {
                                                $this->resetCalculation();
                                            }),

                                        TextInput::make('customer_email')
                                            ->label('Customer Email')
                                            ->email()
                                            ->required()
                                            ->helperText('Points will be credited to this customer')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function () {
                                                $this->resetCalculation();
                                            }),
                                    ])
                                    ->columns(2),

                                Section::make('Calculation Results')
                                    ->schema([
                                        Placeholder::make('calculated_points')
                                            ->label('Total Points Earned')
                                            ->content(fn (): string => $this->calculatedPoints ?? 'Click "Calculate Points" to see results')
                                            ->extraAttributes([
                                                'class' => 'text-2xl font-bold text-green-600'
                                            ]),

                                        Placeholder::make('rule_breakdown')
                                            ->label('Points Breakdown')
                                            ->content(function (): string {
                                                if (empty($this->ruleBreakdown)) {
                                                    return 'No calculation performed yet';
                                                }

                                                $breakdown = '';
                                                foreach ($this->ruleBreakdown as $rule) {
                                                    $breakdown .= "• {$rule['rule_name']}: {$rule['points']} points\n";
                                                }
                                                return $breakdown ?: 'No applicable rules found';
                                            }),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (): bool => !empty($this->calculatedPoints)),

                                Actions::make([
                                    Action::make('calculate')
                                        ->label('Calculate Points')
                                        ->icon('heroicon-o-calculator')
                                        ->color('primary')
                                        ->action('calculatePoints')
                                        ->disabled(function (Get $get): bool {
                                            return empty($get('company_id')) ||
                                                   empty($get('loyalty_program_id')) ||
                                                   empty($get('purchase_amount')) ||
                                                   empty($get('customer_email'));
                                        }),

                                    Action::make('generate_qr')
                                        ->label('Generate QR Code & Credit Points')
                                        ->icon('heroicon-o-qr-code')
                                        ->color('success')
                                        ->action('generateQrAndCreditPoints')
                                        ->visible(fn (): bool => !empty($this->calculatedPoints))
                                        ->requiresConfirmation()
                                        ->modalHeading('Credit Points to Customer')
                                        ->modalDescription('This will generate a QR code. Scanning it will credit the points to the customer account.'),
                                ])->alignEnd(),
                            ]),

                        Tabs\Tab::make('customer_lookup')
                            ->label('Customer Lookup')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Customer')
                                    ->schema([
                                        Select::make('search_company_id')
                                            ->label('Company')
                                            ->options($companyOptions)
                                            ->required()
                                            ->disabled($isHandler && count($companyOptions) === 1)
                                            ->helperText($isHandler ? 'You can only search customers from your assigned companies' : null)
                                            ->live()
                                            ->afterStateUpdated(function ($state) {
                                                // Validate access to selected company
                                                if ($state && !$this->canAccessCompany($state)) {
                                                    Notification::make()
                                                        ->title('Access Denied')
                                                        ->body('You do not have access to this company')
                                                        ->danger()
                                                        ->send();
                                                }
                                                
                                                // Reset customer data when company changes
                                                $this->customerBalance = null;
                                                $this->customerTransactions = [];
                                            }),

                                        TextInput::make('search_customer_email')
                                            ->label('Customer Email')
                                            ->email()
                                            ->required()
                                            ->helperText('Enter customer email to view their points balance'),
                                    ])
                                    ->columns(2),

                                Section::make('Customer Information')
                                    ->schema([
                                        Placeholder::make('customer_balance')
                                            ->label('Current Points Balance')
                                            ->content(fn (): string => $this->customerBalance !== null
                                                ? number_format($this->customerBalance) . ' points'
                                                : 'Search for a customer to see their balance')
                                            ->extraAttributes([
                                                'class' => 'text-2xl font-bold text-blue-600'
                                            ]),

                                        Placeholder::make('transaction_count')
                                            ->label('Total Transactions')
                                            ->content(fn (): string => !empty($this->customerTransactions)
                                                ? count($this->customerTransactions) . ' transactions'
                                                : 'No transactions found'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (): bool => $this->customerBalance !== null),

                                Actions::make([
                                    Action::make('search_customer')
                                        ->label('Search Customer')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('info')
                                        ->action('searchCustomer')
                                        ->disabled(function (Get $get): bool {
                                            return empty($get('search_company_id')) ||
                                                   empty($get('search_customer_email'));
                                        }),
                                ])->alignEnd(),
                            ]),

                        Tabs\Tab::make('redeem_points')
                            ->label('Redeem Points')
                            ->icon('heroicon-o-minus-circle')
                            ->schema([
                                Section::make('Redemption Details')
                                    ->schema([
                                        Select::make('redeem_company_id')
                                            ->label('Company')
                                            ->options($companyOptions)
                                            ->required()
                                            ->disabled($isHandler && count($companyOptions) === 1)
                                            ->helperText($isHandler ? 'You can only redeem points for your assigned companies' : null)
                                            ->live()
                                            ->afterStateUpdated(function ($state) {
                                                // Validate access to selected company
                                                if ($state && !$this->canAccessCompany($state)) {
                                                    Notification::make()
                                                        ->title('Access Denied')
                                                        ->body('You do not have access to this company')
                                                        ->danger()
                                                        ->send();
                                                }
                                                
                                                // Reset redemption QR path when company changes
                                                $this->redemptionQrPath = null;
                                            }),

                                        TextInput::make('redeem_customer_email')
                                            ->label('Customer Email')
                                            ->email()
                                            ->required(),

                                        TextInput::make('redeem_points')
                                            ->label('Points to Redeem')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->suffix('points')
                                            ->helperText('Enter the number of points to redeem'),

                                        Textarea::make('redemption_description')
                                            ->label('Redemption Description')
                                            ->placeholder('e.g., Free coffee, 10% discount, etc.')
                                            ->required()
                                            ->helperText('Describe what the customer is redeeming'),
                                    ])
                                    ->columns(2),

                                Actions::make([
                                    Action::make('generate_redemption_qr')
                                        ->label('Generate Redemption QR')
                                        ->icon('heroicon-o-qr-code')
                                        ->color('warning')
                                        ->action('generateRedemptionQr')
                                        ->disabled(function (Get $get): bool {
                                            return empty($get('redeem_company_id')) ||
                                                   empty($get('redeem_customer_email')) ||
                                                   empty($get('redeem_points')) ||
                                                   empty($get('redemption_description'));
                                        })
                                        ->requiresConfirmation()
                                        ->modalHeading('Generate Redemption QR')
                                        ->modalDescription('This will generate a QR code. Scanning it will confirm the point redemption.'),
                                ])->alignEnd(),
                            ]),
                    ])
                    ->live(),
            ])
            ->statePath('data');
    }

    public function calculatePoints(): void
    {
        try {
            // Validate only the fields relevant to the 'earn_points' tab
            $this->validate([
                'data.company_id' => 'required',
                'data.loyalty_program_id' => 'required',
                'data.purchase_amount' => 'required|numeric|min:0.01',
                'data.customer_email' => 'required|email',
            ]);

            // Additional security check
            if (!$this->canAccessCompany($this->data['company_id'])) {
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have access to this company')
                    ->danger()
                    ->send();
                return;
            }

            Log::info('Calculating points', $this->data);

            $loyaltyProgram = LoyaltyProgram::where('id', $this->data['loyalty_program_id'])
                ->where('company_id', $this->data['company_id']) // Additional security check
                ->first();
            
            $purchaseAmount = (float) $this->data['purchase_amount'];

            if (!$loyaltyProgram) {
                Notification::make()
                    ->title('Error')
                    ->body('Loyalty program not found or access denied')
                    ->danger()
                    ->send();
                return;
            }

            $totalPoints = 0;
            $breakdown = [];

            $rules = $loyaltyProgram->rules()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('active_from_date')
                          ->orWhere('active_from_date', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('active_to_date')
                          ->orWhere('active_to_date', '>=', now());
                })
                ->get();

            Log::info('Found rules', ['count' => $rules->count()]);

            foreach ($rules as $rule) {
                $rulePoints = 0;

                if ($rule->min_purchase_amount && $purchaseAmount < $rule->min_purchase_amount) {
                    continue;
                }

                switch ($rule->rule_type) {
                    case 'purchase_based':
                        if ($rule->amount_per_point > 0) {
                            $rulePoints = floor($purchaseAmount / $rule->amount_per_point) * $rule->points_earned;
                        }
                        break;

                    case 'first_purchase':
                        $rulePoints = $rule->points_earned;
                        break;

                    case 'milestone':
                        if ($purchaseAmount >= ($rule->milestone_amount ?? 1000)) {
                            $rulePoints = $rule->points_earned;
                        }
                        break;
                }

                if ($rulePoints > 0) {
                    $totalPoints += $rulePoints;
                    $breakdown[] = [
                        'rule_name' => $rule->rule_name,
                        'rule_type' => $rule->rule_type,
                        'points' => $rulePoints,
                    ];
                }
            }

            $this->calculatedPoints = number_format($totalPoints) . ' points';
            $this->ruleBreakdown = $breakdown;

            Log::info('Points calculated', ['total' => $totalPoints, 'breakdown' => $breakdown]);

            Notification::make()
                ->title('Points Calculated Successfully!')
                ->body("Customer will earn {$totalPoints} points from this purchase")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Point calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Calculation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function generateQrAndCreditPoints(): void
    {
        try {
            // Validate only the fields relevant to the 'earn_points' tab
            $this->validate([
                'data.company_id' => 'required',
                'data.loyalty_program_id' => 'required',
                'data.purchase_amount' => 'required|numeric|min:0.01',
                'data.customer_email' => 'required|email',
            ]);

            // Additional security check
            if (!$this->canAccessCompany($this->data['company_id'])) {
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have access to this company')
                    ->danger()
                    ->send();
                return;
            }

            Log::info('Generating QR code for earning', $this->data);

            if (empty($this->calculatedPoints)) {
                Notification::make()
                    ->title('Error')
                    ->body('Please calculate points first')
                    ->danger()
                    ->send();
                return;
            }

            $totalPoints = (int) str_replace([' points', ','], '', $this->calculatedPoints);
            $transactionId = 'TXN-' . strtoupper(Str::random(10));

            $customerPoint = CustomerPoint::create([
                'customer_email' => $this->data['customer_email'],
                'company_id' => $this->data['company_id'],
                'loyalty_program_id' => $this->data['loyalty_program_id'],
                'points_earned' => $totalPoints,
                'purchase_amount' => $this->data['purchase_amount'],
                'transaction_id' => $transactionId,
                'transaction_type' => 'earning',
                'status' => 'pending',
                'rule_breakdown' => json_encode($this->ruleBreakdown),
                'created_by' => Auth::id(), // Track who created this transaction
            ]);

            Log::info('Customer point record created (pending)', ['id' => $customerPoint->id, 'transaction_id' => $transactionId]);

            // Generate the webhook URL for earning confirmation
            $webhookUrl = url('/api/loyalty/confirm-earning/' . $transactionId);

            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($webhookUrl);

            $qrFileName = 'qr-codes/' . $transactionId . '.png';

            if (!Storage::disk('public')->exists('qr-codes')) {
                Storage::disk('public')->makeDirectory('qr-codes');
            }

            Storage::disk('public')->put($qrFileName, $qrCode);

            $customerPoint->update(['qr_code_path' => $qrFileName]);

            $this->qrCodePath = asset('storage/' . $qrFileName);

            Log::info('Earning QR Code generated successfully with webhook URL', [
                'transaction_id' => $transactionId,
                'file_path' => $qrFileName,
                'webhook_url' => $webhookUrl
            ]);

            Notification::make()
                ->title('QR Code Generated Successfully!')
                ->body("Scan QR to credit points. Transaction ID: {$transactionId}")
                ->success()
                ->persistent()
                ->send();

        } catch (\Exception $e) {
            Log::error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data ?? []
            ]);

            Notification::make()
                ->title('QR Code Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function searchCustomer(): void
    {
        try {
            // Validate only the fields relevant to the 'customer_lookup' tab
            $this->validate([
                'data.search_company_id' => 'required',
                'data.search_customer_email' => 'required|email',
            ]);

            // Additional security check
            if (!$this->canAccessCompany($this->data['search_company_id'])) {
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have access to this company')
                    ->danger()
                    ->send();
                return;
            }

            $companyId = $this->data['search_company_id'];
            $customerEmail = $this->data['search_customer_email'];

            $this->customerBalance = CustomerPoint::getCustomerBalance($customerEmail, $companyId);
            $this->customerTransactions = CustomerPoint::getCustomerTransactionHistory($customerEmail, $companyId)->toArray();

            Notification::make()
                ->title('Customer Found')
                ->body("Customer has {$this->customerBalance} points available")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Customer search failed', [
                'error' => $e->getMessage(),
                'data' => $this->data ?? []
            ]);

            Notification::make()
                ->title('Search Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function generateRedemptionQr(): void
    {
        try {
            // Validate only the fields relevant to the 'redeem_points' tab
            $this->validate([
                'data.redeem_company_id' => 'required',
                'data.redeem_customer_email' => 'required|email',
                'data.redeem_points' => 'required|numeric|min:1',
                'data.redemption_description' => 'required',
            ]);

            // Additional security check
            if (!$this->canAccessCompany($this->data['redeem_company_id'])) {
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have access to this company')
                    ->danger()
                    ->send();
                return;
            }

            $companyId = $this->data['redeem_company_id'];
            $customerEmail = $this->data['redeem_customer_email'];
            $redeemPoints = (int) $this->data['redeem_points'];

            $currentBalance = CustomerPoint::getCustomerBalance($customerEmail, $companyId);

            if ($currentBalance < $redeemPoints) {
                Notification::make()
                    ->title('Insufficient Points')
                    ->body("Customer only has {$currentBalance} points available")
                    ->danger()
                    ->send();
                return;
            }

            $transactionId = 'RED-' . strtoupper(Str::random(10));

            $redemptionTransaction = CustomerPoint::create([
                'customer_email' => $customerEmail,
                'company_id' => $companyId,
                'loyalty_program_id' => null,
                'points_earned' => -$redeemPoints,
                'purchase_amount' => null,
                'transaction_id' => $transactionId,
                'transaction_type' => 'redemption',
                'status' => 'pending',
                'redemption_description' => $this->data['redemption_description'],
                'created_by' => Auth::id(), // Track who created this transaction
            ]);

            Log::info('Redemption record created (pending)', ['id' => $redemptionTransaction->id, 'transaction_id' => $transactionId]);

            // Generate the webhook URL for redemption confirmation
            $webhookUrl = url('/api/loyalty/confirm-redemption/' . $transactionId);

            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($webhookUrl);

            $qrFileName = 'qr-codes/' . $transactionId . '.png';

            if (!Storage::disk('public')->exists('qr-codes')) {
                Storage::disk('public')->makeDirectory('qr-codes');
            }

            Storage::disk('public')->put($qrFileName, $qrCode);

            $redemptionTransaction->update(['qr_code_path' => $qrFileName]);

            $this->redemptionQrPath = asset('storage/' . $qrFileName);

            Log::info('Redemption QR Code generated successfully with webhook URL', [
                'transaction_id' => $transactionId,
                'file_path' => $qrFileName,
                'webhook_url' => $webhookUrl
            ]);

            Notification::make()
                ->title('Redemption QR Generated Successfully!')
                ->body("Scan QR to confirm redemption. Transaction ID: {$transactionId}")
                ->success()
                ->persistent()
                ->send();

        } catch (\Exception $e) {
            Log::error('Redemption QR generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Redemption QR Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function resetCalculation(): void
    {
        $this->calculatedPoints = null;
        $this->qrCodePath = null;
        $this->ruleBreakdown = [];
    }

    /**
     * Check if the current user can view this page
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return $user->roles()->whereIn('name', ['superadmin', 'handler'])->exists();
    }
}