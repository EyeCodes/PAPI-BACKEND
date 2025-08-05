@php
    $colors = [
        'purchase_based' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'birthday' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'referral_bonus' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'first_purchase' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        'milestone' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
        'seasonal' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    ];
    
    $icons = [
        'purchase_based' => 'heroicon-m-shopping-cart',
        'birthday' => 'heroicon-m-cake',
        'referral_bonus' => 'heroicon-m-user-plus',
        'first_purchase' => 'heroicon-m-star',
        'milestone' => 'heroicon-m-trophy',
        'seasonal' => 'heroicon-m-calendar',
    ];
    
    $labels = [
        'purchase_based' => 'Purchase Based',
        'birthday' => 'Birthday Bonus',
        'referral_bonus' => 'Referral Bonus',
        'first_purchase' => 'First Purchase',
        'milestone' => 'Milestone Achievement',
        'seasonal' => 'Seasonal Promotion',
    ];
    
    $colorClass = $colors[$getState()] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    $icon = $icons[$getState()] ?? 'heroicon-m-tag';
    $label = $labels[$getState()] ?? ucfirst(str_replace('_', ' ', $getState()));
@endphp

<div class="flex items-center gap-1">
    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium {{ $colorClass }}">
        @svg($icon, 'h-3 w-3')
        {{ $label }}
    </span>
</div>
