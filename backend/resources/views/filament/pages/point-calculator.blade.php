<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600 rounded-lg p-6 text-white">
            <h1 class="text-2xl font-bold mb-2">Point Calculator & Customer Manager</h1>
            <p class="text-green-100">Calculate points, search customers, and manage point redemptions</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            {{ $this->form }}
            
            {{-- The actions are now rendered automatically by $this->form --}}
            {{-- No need for the manual loop:
            <div class="mt-6 flex flex-wrap gap-4">
                @foreach($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
            --}}
        </div>

        <!-- Customer Transaction History -->
        @if(!empty($customerTransactions))
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach(array_slice($customerTransactions, 0, 10) as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y g:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $transaction['transaction_type'] === 'earning' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($transaction['transaction_type']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                {{ $transaction['points_earned'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction['points_earned'] > 0 ? '+' : '' }}{{ number_format($transaction['points_earned']) }} pts
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    @switch($transaction['status'])
                                        @case('pending') bg-yellow-100 text-yellow-800 @break
                                        @case('credited') bg-green-100 text-green-800 @break
                                        @case('redeemed') bg-blue-100 text-blue-800 @break
                                        @case('expired') bg-red-100 text-red-800 @break
                                        @default bg-gray-100 text-gray-800
                                    @endswitch">
                                    {{ ucfirst($transaction['status']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
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
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4 text-green-600"> Points Earning QR Code Generated!</h3>
            <div class="flex flex-col items-center space-y-4">
                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                    <img src="{{ $qrCodePath }}" alt="QR Code" class="border rounded-lg shadow-sm bg-white p-2">
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-green-700 mb-2">
                         {{ $calculatedPoints }} Ready to Credit!
                    </p>
                    <p class="text-sm text-gray-600">
                        Customer can scan this QR code to credit the points to their account
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ $qrCodePath }}" download class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download QR Code
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print QR Code
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- QR Code Display for Redemption -->
        @if($redemptionQrPath)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4 text-orange-600"> Points Redemption QR Code Generated!</h3>
            <div class="flex flex-col items-center space-y-4">
                <div class="bg-orange-50 border-2 border-orange-200 rounded-lg p-4">
                    <img src="{{ $redemptionQrPath }}" alt="Redemption QR Code" class="border rounded-lg shadow-sm bg-white p-2">
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-orange-700 mb-2">
                        🔥 {{ $this->data['redeem_points'] ?? 0 }} Points to Redeem!
                    </p>
                    <p class="text-sm text-gray-600">
                        Customer can scan this QR code to confirm the point redemption
                    </p>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center max-w-md">
                    <p class="text-sm text-orange-800">
                        <strong> Redemption:</strong> {{ $this->data['redemption_description'] ?? 'N/A' }}
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ $redemptionQrPath }}" download class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download Redemption QR
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Redemption QR
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
