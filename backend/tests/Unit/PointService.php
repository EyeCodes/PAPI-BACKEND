<?php

namespace Tests\Unit;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\PointsRule;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserMerchantPoints;
use App\Services\PointsService;
use App\Services\FormulaParser;
use App\Enums\PointsRuleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PointService extends TestCase
{
    use RefreshDatabase;

    protected $pointsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pointsService = new PointsService();
    }

    public function test_fixed_rule_awards_fixed_points()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Add password
        ]);
        PointsRule::create([
            'type' => PointsRuleType::Fixed,
            'parameters' => ['points' => 50],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(50, $points, 'Fixed rule should award 50 points');
    }

    public function test_dynamic_rule_calculates_points_based_on_amount()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Add password
        ]);
        PointsRule::create([
            'type' => PointsRuleType::Dynamic,
            'parameters' => ['divisor' => 100, 'multiplier' => 2],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 350.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(6, $points, 'Dynamic rule should award 6 points for 350 PHP');
    }

    public function test_combo_rule_calculates_points_for_amount_and_quantity()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $product = Product::create(['name' => 'Test Product', 'merchant_id' => $merchant->id]);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Add password
        ]);
        PointsRule::create([
            'type' => PointsRuleType::Combo,
            'parameters' => ['divisor' => 100, 'amount_multiplier' => 2, 'quantity_multiplier' => 5],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 250.00,
        ]);
        $transaction->items()->create(['product_id' => $product->id, 'quantity' => 3]);
        $transaction->load('items'); // Reload the items relationship

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(19, $points, 'Combo rule should award 19 points (4 from amount + 15 from quantity)');
    }

    public function test_threshold_rule_awards_points_above_minimum()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Add password
        ]);
        PointsRule::create([
            'type' => PointsRuleType::Threshold,
            'parameters' => ['points' => 100],
            'conditions' => ['min_amount' => 500],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        // Above threshold
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 600.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(100, $points, 'Threshold rule should award 100 points for 600 PHP');

        // Below threshold
        $transaction2 = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 400.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction2);
        $this->assertEquals(0, $points, 'Threshold rule should award 0 points below 500 PHP');
    }

    public function test_first_purchase_rule_applies_once_per_merchant()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Add password
        ]);
        PointsRule::create([
            'type' => PointsRuleType::FirstPurchase,
            'parameters' => ['points' => 50],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        // First transaction
        $transaction1 = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction1);
        $this->assertEquals(50, $points, 'First purchase rule should award 50 points');

        // Second transaction
        $transaction2 = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction2);
        $this->assertEquals(0, $points, 'First purchase rule should award 0 points for subsequent purchases');
    }

    public function test_limited_time_rule_applies_within_date_range()
    {
        $merchant1 = Merchant::create(['name' => 'Test Merchant 1']);
        $merchant2 = Merchant::create(['name' => 'Test Merchant 2']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Active rule for merchant1
        PointsRule::create([
            'type' => PointsRuleType::LimitedTime,
            'parameters' => ['points' => 75],
            'conditions' => [
                'start_date' => now()->subDay()->toDateTimeString(),
                'end_date' => now()->addDay()->toDateTimeString(),
            ],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant1->id,
        ]);

        $transaction1 = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant1->id,
            'amount' => 200.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction1);
        $this->assertEquals(75, $points, 'Limited time rule should award 75 points within date range');

        // Inactive rule for merchant2
        PointsRule::create([
            'type' => PointsRuleType::LimitedTime,
            'parameters' => ['points' => 75],
            'conditions' => [
                'start_date' => now()->addDays(2)->toDateTimeString(),
                'end_date' => now()->addDays(3)->toDateTimeString(),
            ],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant2->id,
        ]);

        $transaction2 = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant2->id,
            'amount' => 200.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction2);
        $this->assertEquals(0, $points, 'Limited time rule should award 0 points outside date range');
    }

    public function test_no_points_rule_disables_points()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'), // Add password
        ]);
        PointsRule::create([
            'type' => PointsRuleType::NoPoints,
            'parameters' => [],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
        ]);

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(0, $points, 'No points rule should disable points');
    }

    public function test_custom_formula_rule_calculates_points()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $product = Product::create(['name' => 'Test Product', 'merchant_id' => $merchant->id]);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create the rule first
        PointsRule::create([
            'type' => PointsRuleType::CustomFormula,
            'parameters' => ['formula' => 'floor(total / 100) * 2 + quantity * 5'],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        // Mock the FormulaParser using alias
        $mock = Mockery::mock(FormulaParser::class);
        $mock->shouldReceive('evaluate')
            ->with('floor(total / 100) * 2 + quantity * 5', ['total' => 250.00, 'quantity' => 3])
            ->andReturn(19);
        $this->app->instance('App\Services\FormulaParser', $mock);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 250.00,
        ]);
        $transaction->items()->create(['product_id' => $product->id, 'quantity' => 3]);
        $transaction->load('items'); // Reload the items relationship

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(19, $points, 'Custom formula rule should award 19 points');
    }

    public function test_rule_priority_applies_multiple_rules()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $merchant2 = Merchant::create(['name' => 'Test Merchant 2']);
        $product = Product::create(['name' => 'Test Product', 'merchant_id' => $merchant->id]);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Merchant rule (lower priority)
        PointsRule::create([
            'type' => PointsRuleType::Fixed,
            'parameters' => ['points' => 50],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        // Product rule (higher priority)
        PointsRule::create([
            'type' => PointsRuleType::Fixed,
            'parameters' => ['points' => 100],
            'priority' => 20,
            'associated_entity_type' => Product::class,
            'associated_entity_id' => $product->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
        ]);

        // Create item and explicitly load relationships
        $item = $transaction->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $transaction->load(['items.product.pointsRules']);

        $points = $this->pointsService->calculatePoints($transaction);
        $this->assertEquals(150, $points, 'Both product (100) and merchant (50) rules should apply');
    }

    public function test_award_points_creates_user_merchant_points_record()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        PointsRule::create([
            'type' => PointsRuleType::Fixed,
            'parameters' => ['points' => 50],
            'priority' => 10,
            'associated_entity_type' => Merchant::class,
            'associated_entity_id' => $merchant->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
        ]);

        // Points are automatically awarded when transaction is created

        // Check if UserMerchantPoints record was created
        $userMerchantPoints = UserMerchantPoints::where('user_id', $user->id)
            ->where('merchant_id', $merchant->id)
            ->first();

        $this->assertNotNull($userMerchantPoints, 'UserMerchantPoints record should be created');
        $this->assertEquals(50, $userMerchantPoints->points, 'Points should be awarded correctly');
        $this->assertEquals(50, $userMerchantPoints->getRawOriginal('total_earned'), 'Total earned should be updated');
        $this->assertNotNull($userMerchantPoints->last_earned_at, 'Last earned timestamp should be set');
    }

    public function test_get_user_merchant_points_returns_correct_balance()
    {
        $merchant = Merchant::create(['name' => 'Test Merchant']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create UserMerchantPoints record
        UserMerchantPoints::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'points' => 150,
            'total_earned' => 200,
            'total_spent' => 50,
        ]);

        $points = $this->pointsService->getUserMerchantPoints($user->id, $merchant->id);
        $this->assertEquals(150, $points, 'Should return correct points balance');
    }

    public function test_get_user_points_summary_returns_all_merchant_points()
    {
        $merchant1 = Merchant::create(['name' => 'Test Merchant 1']);
        $merchant2 = Merchant::create(['name' => 'Test Merchant 2']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create UserMerchantPoints records
        UserMerchantPoints::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant1->id,
            'points' => 100,
            'total_earned' => 150,
            'total_spent' => 50,
        ]);

        UserMerchantPoints::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant2->id,
            'points' => 200,
            'total_earned' => 250,
            'total_spent' => 50,
        ]);

        $summary = $this->pointsService->getUserPointsSummary($user->id);

        $this->assertCount(2, $summary, 'Should return points for both merchants');
        $this->assertEquals('Test Merchant 1', $summary[0]['merchant_name']);
        $this->assertEquals(100, $summary[0]['points']);
        $this->assertEquals('Test Merchant 2', $summary[1]['merchant_name']);
        $this->assertEquals(200, $summary[1]['points']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
