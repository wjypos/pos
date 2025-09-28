<div class="bg-gray-100 p-4 rounded-lg">
    <div class="mb-4">
        <h3 class="text-lg font-medium">Payment Summary</h3>
        <p class="text-sm text-gray-600">Total Transactions: {{ $transactionCount }}</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($totals as $method => $amount)
            <div class="bg-white p-3 rounded shadow-sm">
                <div class="text-sm font-medium text-gray-600">{{ strtoupper($method) }}</div>
                <div class="text-lg font-semibold">Rp {{ number_format($amount, 0) }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 text-right">
        <div class="text-sm text-gray-600">Grand Total</div>
        <div class="text-xl font-bold">Rp {{ number_format($grandTotal, 0) }}</div>
    </div>
</div>
