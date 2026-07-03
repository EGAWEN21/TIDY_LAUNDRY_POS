<div class="dashboard-main-body">
    <!-- Top Filter & Print Row -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
        <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-flex tw-items-center tw-gap-2">
                <label class="tw-text-sm tw-font-medium">{{$lang->data['from'] ?? 'From'}}</label>
                <input type="date" class="form-control bg-base h-40-px w-auto" wire:model.live="from_date">
            </div>
            <div class="tw-flex tw-items-center tw-gap-2">
                <label class="tw-text-sm tw-font-medium">{{$lang->data['to'] ?? 'To'}}</label>
                <input type="date" class="form-control bg-base h-40-px w-auto" wire:model.live="to_date">
            </div>
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
            @can('report_download')
            <button type="button" wire:click="downloadFile()" class="btn btn-warning-100 text-warning-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:download-bold" class="mr-1"></iconify-icon>
                {{$lang->data['download_report'] ?? 'Data (PDF)'}}
            </button>
            @endcan
            <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
                {{$lang->data['print_dashboard'] ?? 'Print Dashboard'}}
            </button>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row row-cols-1 row-cols-md-3 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['total_income'] ?? 'Total Income (Payments)'}}" 
                value="{{ getFormattedCurrency($kpi['income'] ?? 0) }}" 
                icon="mdi:cash-plus" 
                color="success" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['total_expenses'] ?? 'Total Expenses'}}" 
                value="{{ getFormattedCurrency($kpi['expenses'] ?? 0) }}" 
                icon="mdi:cash-minus" 
                color="danger" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['net_profit'] ?? 'Net Profit'}}" 
                value="{{ getFormattedCurrency($kpi['net_profit'] ?? 0) }}" 
                icon="mdi:scale-balance" 
                color="{{ ($kpi['net_profit'] ?? 0) >= 0 ? 'primary' : 'danger' }}" 
                trend="{{ ($kpi['net_profit'] ?? 0) >= 0 ? 'Profitable' : 'Running at a loss' }}"
                trendUp="{{ ($kpi['net_profit'] ?? 0) >= 0 ? true : false }}" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 tw-mb-6">
        <!-- Expense Category Breakdown (100% Stacked Bar) -->
        <div class="col-12">
            <x-chart-container title="{{$lang->data['expense_breakdown'] ?? 'Expense Category Breakdown (100% Stacked)'}}">
                <div id="expenseChart"></div>
            </x-chart-container>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm tw-min-h-[calc(100vh-16rem)]">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">{{ $lang->data['date'] ?? 'Date' }}</th>
                            <th scope="col">{{ $lang->data['expense_category'] ?? 'Category' }}</th>
                            <th scope="col">{{ $lang->data['amount'] ?? 'Amount' }}</th>
                            <th scope="col">{{ $lang->data['note'] ?? 'Note' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $item)
                        <tr>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->expense_date)->format('d/m/Y') }}</p></td>
                            <td>
                                <span class="badge fw-semibold text-primary-600 bg-primary-100 px-20 py-9 radius-4 text-white">
                                    {{ $item->expenseCategory->expense_category_name ?? 'N/A' }}
                                </span>
                            </td>
                            <td><p class="text-sm font-weight-bold text-danger mb-0">{{ getFormattedCurrency($item->expense_amount) }}</p></td>
                            <td><p class="text-sm text-muted mb-0">{{ $item->note ?? '-' }}</p></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No expenses found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('livewire:initialized', () => {
        // --- Expense Breakdown (100% Stacked Horizontal Bar) ---
        function getExpenseSeries(expenseData) {
            var series = [];
            if(!expenseData || expenseData.length === 0) {
                return [{name: 'No Expenses', data: [0]}];
            }
            expenseData.forEach(function(item) {
                series.push({
                    name: item.name,
                    data: [item.amount]
                });
            });
            return series;
        }

        var expenseOptions = {
            series: getExpenseSeries(@json($categoryBreakdown)),
            chart: { type: 'bar', height: 120, stacked: true, stackType: '100%', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '50%' } },
            stroke: { width: 1, colors: ['#fff'] },
            xaxis: { categories: ['Expenses'], labels: { show: false }, axisBorder: {show: false}, axisTicks: {show: false} },
            yaxis: { show: false },
            tooltip: { y: { formatter: function(val) { return "₦" + val.toFixed(2) } } },
            fill: { opacity: 1 },
            legend: { position: 'bottom' },
            colors: ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#0dcaf0', '#0d6efd', '#6f42c1']
        };
        var expenseChart = new ApexCharts(document.querySelector("#expenseChart"), expenseOptions);
        expenseChart.render();
        
        // --- Livewire Re-render on Date Change ---
        Livewire.on('update-expense-charts', (event) => {
            const data = event[0];
            if(data && data.categories) {
                expenseChart.updateSeries(getExpenseSeries(data.categories));
            }
        });
    });
</script>
@endpush