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
            <x-report-granularity />
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
            @can('report_download')
            <button type="button" wire:click="downloadCsv()" class="btn btn-warning-100 text-warning-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:download-bold" class="mr-1"></iconify-icon>
                {{$lang->data['download_csv'] ?? 'Download CSV'}}
            </button>
            <button type="button" wire:click="downloadFile()" class="btn btn-warning-100 text-warning-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:download-bold" class="mr-1"></iconify-icon>
                {{$lang->data['download_report'] ?? 'Data (PDF)'}}
            </button>
            @endcan
            <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
                {{$lang->data['print_report'] ?? 'Print Report'}}
            </button>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="{{ $lang->data['revenue_billed'] ?? 'Revenue Billed' }}" 
                value="{{ getFormattedCurrency($kpi['revenue_billed'] ?? 0) }}" 
                icon="mdi:cash-register" 
                color="info" 
                :trend="($kpi['revenue_billed'] ?? 0) > ($kpi['income'] ?? 0) ? getFormattedCurrency(($kpi['revenue_billed'] ?? 0) - ($kpi['income'] ?? 0)) . ' uncollected' : null"
                :trendUp="false" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{ $lang->data['cash_collected'] ?? 'Cash Collected' }}" 
                value="{{ getFormattedCurrency($kpi['income'] ?? 0) }}" 
                icon="mdi:cash-plus" 
                color="success" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{ $lang->data['total_expenses'] ?? 'Total Expenses' }}" 
                value="{{ getFormattedCurrency($kpi['expenses'] ?? 0) }}" 
                icon="mdi:cash-minus" 
                color="danger" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{ $lang->data['net_cash_position'] ?? 'Net Cash Position' }}" 
                value="{{ getFormattedCurrency($kpi['net_cash'] ?? 0) }}" 
                icon="mdi:scale-balance" 
                color="{{ ($kpi['net_cash'] ?? 0) >= 0 ? 'primary' : 'danger' }}" 
                trend="{{ ($kpi['net_cash'] ?? 0) >= 0 ? 'Cash positive' : 'Cash negative' }}"
                :trendUp="($kpi['net_cash'] ?? 0) >= 0" />
            <p class="text-xs text-muted mt-1 mb-0 tw-px-3">{{ $lang->data['net_cash_note'] ?? 'Cash received minus cash spent' }}</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 tw-mb-6">
        <!-- 6 Month Trend Dual-Line Chart -->
        <div class="col-12 col-xl-6">
            <x-chart-container title="{{$lang->data['cash_vs_expenses'] ?? 'Cash Collected vs Expenses (6 Months)'}}">
                <div id="trendChart"></div>
            </x-chart-container>
        </div>
        <!-- Expense Category Breakdown (Vertical Grouped Bar) -->
        <div class="col-12 col-xl-6">
            <x-chart-container title="{{$lang->data['expense_breakdown'] ?? 'Category Breakdown (This vs Prev Period)'}}">
                <div id="expenseChart"></div>
            </x-chart-container>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card h-100 p-0 radius-12">
        <div class="card-header tw-flex tw-justify-between tw-items-center">
            <h5 class="card-title">{{ $lang->data['expenses_list'] ?? 'Expenses List' }}</h5>
            <div>
                <select class="form-select" wire:model.live="categoryFilter">
                    <option value="">{{ $lang->data['all_categories'] ?? 'All Categories' }}</option>
                    @foreach($expenseCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->expense_category_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm tw-min-h-[calc(100vh-16rem)]">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="tw-cursor-pointer" wire:click="sortBy('expense_date')">
                                {{ $lang->data['date'] ?? 'Date' }}
                                @if($sortBy === 'expense_date')
                                    <iconify-icon icon="ph:caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></iconify-icon>
                                @endif
                            </th>
                            <th scope="col" class="tw-cursor-pointer" wire:click="sortBy('category')">
                                {{ $lang->data['expense_category'] ?? 'Category' }}
                                @if($sortBy === 'category')
                                    <iconify-icon icon="ph:caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></iconify-icon>
                                @endif
                            </th>
                            <th scope="col" class="tw-cursor-pointer" wire:click="sortBy('expense_amount')">
                                {{ $lang->data['amount'] ?? 'Amount' }}
                                @if($sortBy === 'expense_amount')
                                    <iconify-icon icon="ph:caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></iconify-icon>
                                @endif
                            </th>
                            <th scope="col">{{ $lang->data['note'] ?? 'Note' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->expenses as $item)
                        <tr>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->expense_date)->format('d/m/Y') }}</p></td>
                            <td>
                                <span class="badge fw-semibold text-primary-600 bg-primary-100 px-20 py-9 radius-4">
                                    {{ $item->expenseCategory->expense_category_name ?? ($lang->data['uncategorized'] ?? 'Uncategorized') }}
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
        // --- 6 Month Trend Chart (Dual-Line) ---
        var trendOptions = {
            series: [{
                name: 'Cash Collected',
                data: @json($trendData['cash_collected'] ?? [])
            }, {
                name: 'Expenses',
                data: @json($trendData['expenses'] ?? [])
            }],
            chart: { type: 'line', height: 350, toolbar: { show: false } },
            colors: ['#28a745', '#dc3545'],
            stroke: { width: 3, curve: 'smooth' },
            xaxis: { categories: @json($trendLabels) },
            yaxis: {
                labels: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } }
            },
            legend: { position: 'top' },
            tooltip: { y: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } } }
        };
        var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();

        // --- Expense Category Breakdown (Vertical Grouped Bar) ---
        function getExpenseCategories(expenseData) {
            if(!expenseData || expenseData.length === 0) return ['No Expenses'];
            return expenseData.map(item => item.name);
        }
        function getExpenseSeries(expenseData) {
            var series = [];
            if(!expenseData || expenseData.length === 0) {
                return [{name: 'This Period', data: [0]}, {name: 'Previous Period', data: [0]}];
            }
            series.push({
                name: 'This Period',
                data: expenseData.map(item => item.current_amount)
            });
            series.push({
                name: 'Previous Period',
                data: expenseData.map(item => item.previous_amount)
            });
            return series;
        }

        var expenseOptions = {
            series: getExpenseSeries(@json($categoryBreakdown)),
            chart: { type: 'bar', height: 350, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '50%', endingShape: 'rounded' } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: { categories: getExpenseCategories(@json($categoryBreakdown)) },
            yaxis: {
                labels: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } }
            },
            fill: { opacity: 1 },
            tooltip: { y: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } } },
            colors: ['#0d6efd', '#6c757d']
        };
        var expenseChart = new ApexCharts(document.querySelector("#expenseChart"), expenseOptions);
        expenseChart.render();
        
        // --- Livewire Re-render on Date Change ---
        Livewire.on('update-expense-charts', (event) => {
            const data = event[0];
            if(data) {
                if(data.categories) {
                    expenseChart.updateOptions({
                        xaxis: { categories: getExpenseCategories(data.categories) }
                    });
                    expenseChart.updateSeries(getExpenseSeries(data.categories));
                }
                if(data.trendLabels && data.trendData) {
                    trendChart.updateOptions({
                        xaxis: { categories: data.trendLabels }
                    });
                    trendChart.updateSeries([{
                        name: 'Cash Collected',
                        data: data.trendData.cash_collected
                    }, {
                        name: 'Expenses',
                        data: data.trendData.expenses
                    }]);
                }
            }
        });
    });
</script>
@endpush