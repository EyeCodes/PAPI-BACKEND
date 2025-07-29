<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liability extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'amount',
        'monthly_payment',
        'type',
        'currency',
        'due_date',
        'interest_rate',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the liability.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get liability types
     */
    public static function getTypes(): array
    {
        return [
            'credit_card' => 'Credit Card',
            'personal_loan' => 'Personal Loan',
            'car_loan' => 'Car Loan',
            'mortgage' => 'Mortgage',
            'student_loan' => 'Student Loan',
            'business_loan' => 'Business Loan',
            'medical_debt' => 'Medical Debt',
            'tax_debt' => 'Tax Debt',
            'other' => 'Other',
        ];
    }

    /**
     * Get liability statuses
     */
    public static function getStatuses(): array
    {
        return [
            'active' => 'Active',
            'paid' => 'Paid',
            'defaulted' => 'Defaulted',
            'settled' => 'Settled',
        ];
    }

    /**
     * Scope to get liabilities by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get active liabilities
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get total amount of liabilities for a user
     */
    public static function getTotalAmount(int $userId): float
    {
        return static::where('user_id', $userId)->sum('amount');
    }

    /**
     * Get total monthly payments for a user
     */
    public static function getTotalMonthlyPayments(int $userId): float
    {
        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->sum('monthly_payment');
    }
}
