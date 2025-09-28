<x-filament::page>
    <div class="space-y-6">
        <!-- Today's Stats Cards -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow p-4">
            <h3 class="text-sm font-medium text-gray-500">Today's Sales</h3>
            <p class="mt-2 text-2xl font-semibold">Rp {{ number_format($todayStats['total_sales'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
            <h3 class="text-sm font-medium text-gray-500">Transactions</h3>
            <p class="mt-2 text-2xl font-semibold">{{ $todayStats['transaction_count'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
            <h3 class="text-sm font-medium text-gray-500">Average Sale</h3>
            <p class="mt-2 text-2xl font-semibold">Rp {{ number_format($todayStats['average_sale'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4">
            <h3 class="text-sm font-medium text-gray-500">Total Discount</h3>
            <p class="mt-2 text-2xl font-semibold text-red-600">Rp {{ number_format($todayStats['total_discount'], 0) }}</p>
            </div>
        </div>

        <!-- Sales Chart -->
        <div class="bg-white rounded-xl shadow p-4">
            <h3 class="text-lg font-medium mb-4">Daily Sales (Last 30 Days)</h3>
            <div class="h-[300px]">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Payment Methods -->
            <div class="bg-white rounded-xl shadow p-4">
                <h3 class="text-lg font-medium mb-4">Today's Payment Methods</h3>
                <div class="space-y-2">
                    @foreach($paymentMethodStats as $stat)
                        <div class="flex justify-between items-center">
                            <span class="text-sm">{{ strtoupper($stat->payment_method) }}</span>
                            <span class="font-medium">Rp {{ number_format($stat->total_amount, 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-xl shadow p-4">
                <h3 class="text-lg font-medium mb-4">Top Products Today</h3>
                <div class="space-y-2">
                    @foreach($topProducts as $product)
                        <div class="flex justify-between items-center">
                            <span class="text-sm">{{ $product->name }}</span>
                            <span class="font-medium">{{ $product->total_quantity }} sold</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium">Recent Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2">Payment</th>
                            <th class="px-4 py-2">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($recentTransactions as $transaction)
                            <tr>
                                <td class="px-4 py-2">{{ $transaction->transaction_identifier }}</td>
                                <td class="px-4 py-2">{{ $transaction->customer?->name ?? 'Walk-in' }}</td>
                                <td class="px-4 py-2 text-right">Rp {{ number_format($transaction->final_amount, 0) }}</td>
                                <td class="px-4 py-2 text-center">{{ $transaction->payment_method }}</td>
                                <td class="px-4 py-2 text-center">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($dailySales->pluck('date')),
                    datasets: [{
                        label: 'Daily Sales',
                        data: @json($dailySales->pluck('total_amount')),
                        borderColor: 'rgb(59, 130, 246)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
</x-filament::page>