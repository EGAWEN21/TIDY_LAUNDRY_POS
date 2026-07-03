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
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['total_sales'] ?? 'Total Sales (Completed)'}}" 
                value="{{ getFormattedCurrency($kpi['sales'] ?? 0) }}" 
                icon="mdi:cash-register" 
                color="success" 
                trend="{{ ($kpi['growth'] ?? 0) > 0 ? '+' : '' }}{{ $kpi['growth'] ?? 0 }}% vs previous period"
                trendUp="{{ ($kpi['growth'] ?? 0) >= 0 ? true : false }}" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['total_orders'] ?? 'Total Orders'}}" 
                value="{{ $kpi['orders'] ?? 0 }}" 
                icon="akar-icons:cart" 
                color="primary" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['aov'] ?? 'Avg Order Value'}}" 
                value="{{ getFormattedCurrency($kpi['aov'] ?? 0) }}" 
                icon="mdi:chart-line-variant" 
                color="info" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="{{$lang->data['discount_leakage'] ?? 'Discount Leakage'}}" 
                value="{{ getFormattedCurrency($kpi['discount'] ?? 0) }}" 
                icon="mdi:ticket-percent-outline" 
                color="warning" 
                trend="Money given away"
                trendUp="false" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 tw-mb-6">
        <!-- Service Breakdown (100% Stacked Bar) -->
        <div class="col-12">
            <x-chart-container title="{{$lang->data['service_breakdown'] ?? 'Service Revenue Breakdown (100% Stacked)'}}">
                <div id="serviceChart"></div>
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
                            <th scope="col">{{ $lang->data['order_id'] ?? 'Order ID' }}</th>
                            <th scope="col">{{ $lang->data['date'] ?? 'Date' }}</th>
                            <th scope="col">{{ $lang->data['customer'] ?? 'Customer' }}</th>
                            <th scope="col">{{ $lang->data['discount'] ?? 'Discount' }}</th>
                            <th scope="col">{{ $lang->data['total'] ?? 'Total' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $item)
                        <tr>
                            <td><span class="text-primary fw-bold">{{ $item->order_number }}</span></td>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') }}</p></td>
                            <td>
                                <p class="text-sm font-weight-bold tw-text-black mb-0">{{ $item->customer_name }}</p>
                                <span class="text-xs text-muted">{{ $item->customer_phone }}</span>
                            </td>
                            <td><p class="text-sm mb-0 text-danger">{{ getFormattedCurrency($item->discount) }}</p></td>
                            <td><p class="text-sm font-weight-bold text-success mb-0">{{ getFormattedCurrency($item->total) }}</p></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No sales found for this period.</td>
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
        // --- Service Breakdown (100% Stacked Horizontal Bar) ---
        function getServiceSeries(serviceData) {
            var series = [];
            if(!serviceData || serviceData.length === 0) {
                return [{name: 'No Services', data: [0]}];
            }
            serviceData.forEach(function(item) {
                series.push({
                    name: item.name,
                    data: [item.amount]
                });
            });
            return series;
        }

        var serviceOptions = {
            series: getServiceSeries(@json($serviceBreakdown)),
            chart: { type: 'bar', height: 180, stacked: true, stackType: '100%', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '50%' } },
            stroke: { width: 1, colors: ['#fff'] },
            xaxis: { categories: ['Revenue'], labels: { show: false }, axisBorder: {show: false}, axisTicks: {show: false} },
            yaxis: { show: false },
            tooltip: { y: { formatter: function(val) { return "₦" + val.toFixed(2) } } },
            fill: { opacity: 1 },
            legend: { position: 'bottom' },
            colors: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1', '#0dcaf0']
        };
        var serviceChart = new ApexCharts(document.querySelector("#serviceChart"), serviceOptions);
        serviceChart.render();
        
        // --- Livewire Re-render on Date Change ---
        Livewire.on('update-sales-charts', (event) => {
            const data = event[0];
            if(data && data.services) {
                serviceChart.updateSeries(getServiceSeries(data.services));
            }
        });
    });
</script>
@endpush
