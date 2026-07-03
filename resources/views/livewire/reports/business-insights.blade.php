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
        <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
            <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
            {{$lang->data['print_dashboard'] ?? 'Print Dashboard'}}
        </button>
    </div>

    <!-- KPI Cards Row -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['active_customers'] ?? 'Active Customers'}}" 
                value="{{ $kpi['health']['active'] ?? 0 }}" 
                icon="mdi:account-check" 
                color="success" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['at_risk_customers'] ?? 'At-Risk (> 21 Days)'}}" 
                value="{{ $kpi['health']['at_risk'] ?? 0 }}" 
                icon="mdi:account-alert" 
                color="danger" 
                trend="Requires Attention" 
                trendUp="false" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['on_time_delivery'] ?? 'On-Time Delivery'}}" 
                value="{{ $kpi['tat']['on_time'] ?? 0 }}" 
                icon="mdi:clock-check-outline" 
                color="primary" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['delayed_orders'] ?? 'Delayed Orders'}}" 
                value="{{ $kpi['tat']['delayed'] ?? 0 }}" 
                icon="mdi:clock-alert-outline" 
                color="warning" 
                trendUp="false" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Churn Chart -->
        <div class="col-12 col-lg-6">
            <x-chart-container title="{{$lang->data['customer_health'] ?? 'Customer Health (Active vs At-Risk)'}}">
                <div id="churnChart"></div>
            </x-chart-container>
        </div>
        <!-- Staff Performance -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 p-4 radius-12 border-0 shadow-sm tw-bg-white">
                <h6 class="mb-4 fw-bold">{{$lang->data['top_staff'] ?? 'Top Staff by Revenue'}}</h6>
                <div class="table-responsive">
                    <table class="table bordered-table sm-table">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Orders Processed</th>
                                <th>Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($kpi['staff']) && count($kpi['staff']) > 0)
                                @foreach($kpi['staff'] as $staff)
                                <tr>
                                    <td class="tw-font-medium">{{$staff->name}}</td>
                                    <td>{{$staff->total_orders}}</td>
                                    <td class="text-success tw-font-bold">{{getFormattedCurrency($staff->total_revenue)}}</td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No data available for this period.</td>
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
        // Render Churn Chart
        var churnOptions = {
            series: [{{ $kpi['health']['active'] ?? 0 }}, {{ $kpi['health']['at_risk'] ?? 0 }}],
            chart: { type: 'donut', height: 300 },
            labels: ['Active', 'At-Risk'],
            colors: ['#28a745', '#dc3545'],
            dataLabels: { enabled: true, formatter: function (val) { return val.toFixed(1) + "%" } },
            legend: { position: 'bottom' }
        };
        var churnChart = new ApexCharts(document.querySelector("#churnChart"), churnOptions);
        churnChart.render();
        
        // Re-render charts when Livewire updates the data
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                const kpi = snapshot.data.kpi;
                if(kpi && kpi.health) {
                    churnChart.updateSeries([kpi.health.active, kpi.health.at_risk]);
                }
            })
        });
    });
</script>
@endpush
