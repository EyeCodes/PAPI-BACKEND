<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'amount',
        'currency',
        'merchant_name',
        'merchant_id',
        'financial_category_id',
        'ai_categorized_category',
        'asset_type',
        'asset_value',
        'liability_amount',
        'monthly_payment',
        'liability_type',
        'interest_rate',
        'due_date',
        'ai_analysis',
        'metadata',
        'purchase_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'asset_value' => 'decimal:2',
        'liability_amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'ai_analysis' => 'array',
        'metadata' => 'array',
        'purchase_date' => 'date',
        'due_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function financialCategory(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('financial_category_id', $categoryId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('purchase_date', [$startDate, $endDate]);
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getMerchantDisplayNameAttribute(): string
    {
        return $this->merchant_name ?? $this->merchant?->name ?? 'Unknown Merchant';
    }

    public function getCategoryDisplayNameAttribute(): string
    {
        return $this->ai_categorized_category ?? $this->financialCategory?->name ?? 'Uncategorized';
    }

    public function isAsset(): bool
    {
        return $this->asset_type === 'asset';
    }

    public function isLiability(): bool
    {
        return $this->asset_type === 'liability';
    }

    public function isExpense(): bool
    {
        return $this->asset_type === null;
    }

    public function getAssetValueAttribute(): float
    {
        return $this->asset_value ?? $this->amount;
    }

    public function getLiabilityAmountAttribute(): float
    {
        return $this->liability_amount ?? $this->amount;
    }
}
