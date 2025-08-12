<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600 dark:from-green-400 dark:to-blue-400 rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">
                Point Calculator & Customer Manager
            </h1>
            <p class="text-green-800 dark:text-green-100">
                Calculate points, search customers, and manage point redemptions
            </p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            {{ $this->form }}
        </div>

        <!-- Customer Transaction History -->
        @if(!empty($customerTransactions))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold mb-4 dark:text-gray-100">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach(array_slice($customerTransactions, 0, 10) as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y g:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $transaction['transaction_type'] === 'earning' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ ucfirst($transaction['transaction_type']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                {{ $transaction['points_earned'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction['points_earned'] > 0 ? '+' : '' }}{{ number_format($transaction['points_earned']) }} pts
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    @switch($transaction['status'])
                                        @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                                        @case('credited') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                                        @case('redeemed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                                        @case('expired') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                                        @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endswitch">
                                    {{ ucfirst($transaction['status']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ $transaction['redemption_description'] ?? ($transaction['purchase_amount'] ? 'Purchase: PHP ' . number_format($transaction['purchase_amount'], 2) : 'N/A') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- QR Code Display for Earning -->
        @if($qrCodePath)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold mb-4 text-green-600 dark:text-green-400">Points Earning QR Code Generated!</h3>
            <div class="flex flex-col items-center space-y-4">
                <div class="bg-green-50 dark:bg-green-900 border-2 border-green-200 dark:border-green-700 rounded-lg p-4">
                    <img src="{{ $qrCodePath }}" alt="QR Code" class="border rounded-lg shadow-sm bg-white dark:bg-gray-900 p-2">
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-green-700 dark:text-green-300 mb-2">
                         {{ $calculatedPoints }} Ready to Credit!
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Customer can scan this QR code to credit the points to their account
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ $qrCodePath }}" download class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Download QR Code
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Print QR Code
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- QR Code Display for Redemption -->
        @if($redemptionQrPath)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold mb-4 text-orange-600 dark:text-orange-400">Points Redemption QR Code Generated!</h3>
            <div class="flex flex-col items-center space-y-4">
                <div class="bg-orange-50 dark:bg-orange-900 border-2 border-orange-200 dark:border-orange-700 rounded-lg p-4">
                    <img src="{{ $redemptionQrPath }}" alt="Redemption QR Code" class="border rounded-lg shadow-sm bg-white dark:bg-gray-900 p-2">
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-orange-700 dark:text-orange-300 mb-2">
                        🔥 {{ $this->data['redeem_points'] ?? 0 }} Points to Redeem!
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Customer can scan this QR code to confirm the point redemption
                    </p>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900 border border-orange-200 dark:border-orange-700 rounded-lg p-4 text-center max-w-md">
                    <p class="text-sm text-orange-800 dark:text-orange-200">
                        <strong> Redemption:</strong> {{ $this->data['redemption_description'] ?? 'N/A' }}
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ $redemptionQrPath }}" download class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        Download Redemption QR
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Print Redemption QR
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
