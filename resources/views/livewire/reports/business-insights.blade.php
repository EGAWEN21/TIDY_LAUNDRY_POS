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
            <x-report-granularity :lang="$lang" />
        </div>
        <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
            <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
            {{$lang->data['print_report'] ?? 'Print Report'}}
        </button>
    </div>

    <!-- KPI Cards Row 1 (Operations) -->
    <h6 class="mb-3">Operations Health</h6>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="Avg TAT" 
                value="{{ ($operationsHealth['avg_tat'] ?? 0) . ' days' }}" 
                icon="mdi:timer-sand" 
                color="info" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="On-Time %" 
                value="{{ ($operationsHealth['on_time_pct'] ?? 0) . '%' }}" 
                icon="mdi:clock-check-outline" 
                color="success" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Return Rate" 
                value="{{ ($operationsHealth['return_rate'] ?? 0) . '%' }}" 
                icon="mdi:keyboard-return" 
                color="warning" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Overdue Now" 
                value="{{ $operationsHealth['overdue_count'] ?? 0 }}" 
                icon="mdi:clock-alert-outline" 
                color="danger" />
        </div>
    </div>

    <!-- KPI Cards Row 2 (Business) -->
    <h6 class="mb-3">Business Health</h6>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="Collection Rate" 
                value="{{ ($businessHealth['collection_rate'] ?? 0) . '%' }}" 
                icon="mdi:cash-check" 
                color="success" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Avg Order Value" 
                value="{{ getFormattedCurrency($businessHealth['aov'] ?? 0) }}" 
                icon="mdi:cart-outline" 
                color="primary"
                trend="{{ ($businessHealth['aov_trend_up'] ?? false) ? 'Up from previous' : 'Down from previous' }}"
                :trendUp="!empty($businessHealth['aov_trend_up'])" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Net Cash Position" 
                value="{{ getFormattedCurrency($businessHealth['net_cash_position'] ?? 0) }}" 
                icon="mdi:bank-outline" 
                color="info" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="New Customers" 
                value="{{ $businessHealth['new_customers_count'] ?? 0 }}" 
                icon="mdi:account-plus-outline" 
                color="secondary" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Revenue Trajectory Chart -->
        <div class="col-12 col-lg-6">
            <x-chart-container title="6-Month Revenue Trajectory">
                <div id="revenueChart"></div>
            </x-chart-container>
        </div>
        <!-- Staff Performance -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 p-4 radius-12 border-0 shadow-sm tw-bg-white">
                <h6 class="mb-4 fw-bold">{{$lang->data['top_staff'] ?? 'Staff Performance'}}</h6>
                <div class="table-responsive">
                    <table class="table bordered-table sm-table">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Revenue Generated</th>
                                <th>AOV</th>
                                <th>Returns %</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($staffPerformance) && count($staffPerformance) > 0)
                                @foreach($staffPerformance as $staff)
                                <tr>
                                    <td class="tw-font-medium">{{$staff['name']}}</td>
                                    <td class="text-success tw-font-bold">{{getFormattedCurrency($staff['total_revenue'])}}</td>
                                    <td>{{getFormattedCurrency($staff['aov_per_staff'])}}</td>
                                    <td>{{$staff['return_rate_per_staff']}}%</td>
                                    <td>
                                        @if($staff['trend_up'])
                                            <span class="text-success tw-flex tw-items-center"><iconify-icon icon="mdi:arrow-up"></iconify-icon></span>
                                        @else
                                            <span class="text-danger tw-flex tw-items-center"><iconify-icon icon="mdi:arrow-down"></iconify-icon></span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No data available for this period.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('livewire:initialized', () => {
        // Render Revenue Chart
        var revenueData = @json($monthlyRevenueTrend);
        var categories = revenueData.map(item => item.month);
        var seriesData = revenueData.map(item => item.revenue);

        var revenueOptions = {
            series: [{ name: 'Revenue', data: seriesData }],
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: '50%',
                }
            },
            dataLabels: { enabled: false },
            xaxis: { categories: categories },
            colors: ['#0d6efd'],
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val;
                    }
                }
            }
        };
        var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
        revenueChart.render();
        
        // Re-render charts when Livewire updates the data
        Livewire.on('update-insights-chart', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            const trend = data ? data.monthlyRevenueTrend : null;
            if (trend && revenueChart) {
                revenueChart.updateSeries([{ name: 'Revenue', data: trend.map(item => item.revenue) }]);
                revenueChart.updateOptions({ xaxis: { categories: trend.map(item => item.month) } });
            }
        });
    });
</script>
@endpush
