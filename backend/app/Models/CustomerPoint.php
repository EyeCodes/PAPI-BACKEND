<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CustomerPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_email',
        'company_id',
        'loyalty_program_id',
        'transaction_id',
        'points_earned',
        'purchase_amount',
        'transaction_type',
        'status',
        'rule_breakdown',
        'credited_at',
        'redeemed_at',
        'redemption_description',
        'transaction_date',
        'qr_code_path',

    ];

    protected $casts = [
        'points_earned' => 'integer',
        'purchase_amount' => 'decimal:2',
        'rule_breakdown' => 'array',
        'credited_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function scopeForCustomer(Builder $query, string $email): Builder
    {
        return $query->where('customer_email', $email);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCredited(Builder $query): Builder
    {
        return $query->where('status', 'credited');
    }

    public function scopeEarnings(Builder $query): Builder
    {
        return $query->where('transaction_type', 'earning');
    }

    public function scopeRedemptions(Builder $query): Builder
    {
        return $query->where('transaction_type', 'redemption');
    }

    public static function getCustomerBalance(string $email, int $companyId): int
{
    return self::forCustomer($email)
        ->forCompany($companyId)
        ->credited()
        ->sum('points_earned');
}

    public static function getCustomerTransactionHistory(string $email, int $companyId)
    {
        return self::forCustomer($email)
            ->forCompany($companyId)
            ->with(['loyaltyProgram', 'company'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }
   
    public function creditPoints(): bool
    {
        if ($this->status === 'pending' && $this->transaction_type === 'earning') {
            $this->update(['status' => 'credited']);
            return true;
        }
        return false;
    }

    // Redeem points (change status from pending to redeemed)
    public function redeemPoints(): bool
{
    if ($this->status === 'pending' && $this->transaction_type === 'redemption') {
        $this->update(['status' => 'credited']); // Use 'credited' status for both earnings and redemptions
        return true;
    }
    return false;
}
    
}
