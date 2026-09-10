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
            <div class="tw-flex tw-items-center tw-gap-2">
                <label class="tw-text-sm tw-font-medium">{{$lang->data['status'] ?? 'Status'}}</label>
                <select class="form-select form-select-sm bg-base h-40-px w-auto" wire:model.live="status">
                    <option value="-1">{{$lang->data['all_orders'] ?? 'All Orders'}}</option>
                    <option value="0">{{$lang->data['pending'] ?? 'Pending'}}</option>
                    <option value="1">{{$lang->data['processing'] ?? 'Processing'}}</option>
                    <option value="2">{{$lang->data['ready_to_deliver'] ?? 'Ready To Deliver'}}</option>
                    <option value="3">{{$lang->data['delivered'] ?? 'Delivered'}}</option>
                    <option value="4">{{$lang->data['returned'] ?? 'Returned'}}</option>
                </select>
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
                title="{{$lang->data['total_orders'] ?? 'Total Orders'}}" 
                value="{{ $kpi['orders'] ?? 0 }}" 
                icon="akar-icons:cart" 
                color="primary" 
                trend="{{ ($kpi['growth'] ?? 0) > 0 ? '+' : '' }}{{ $kpi['growth'] ?? 0 }}% vs previous period"
                trendUp="{{ ($kpi['growth'] ?? 0) >= 0 ? true : false }}" />
        </div>
        <div class="col-8">
            <x-chart-container title="{{$lang->data['service_volume_breakdown'] ?? 'Service Volume Breakdown (Items Processed)'}}">
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
                            <th scope="col">{{ $lang->data['status'] ?? 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->orders as $item)
                        <tr>
                            <td><span class="text-primary fw-bold">{{ $item->order_number }}</span></td>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') }}</p></td>
                            <td>
                                <p class="text-sm font-weight-bold tw-text-black mb-0">{{ $item->customer_name }}</p>
                            </td>
                            <td>
                                @if($item->status == 0)
                                    <span class="badge fw-semibold text-warning-600 bg-warning-100 px-20 py-9 radius-4 text-white">{{ $lang->data['pending'] ?? 'Pending' }}</span>
                                @elseif($item->status == 1)
                                    <span class="badge fw-semibold text-info-600 bg-info-100 px-20 py-9 radius-4 text-white">{{ $lang->data['processing'] ?? 'Processing' }}</span>
                                @elseif($item->status == 2)
                                    <span class="badge fw-semibold text-primary-600 bg-primary-100 px-20 py-9 radius-4 text-white">{{ $lang->data['ready_to_deliver'] ?? 'Ready To Deliver' }}</span>
                                @elseif($item->status == 3)
                                    <span class="badge fw-semibold text-success-600 bg-success-100 px-20 py-9 radius-4 text-white">{{ $lang->data['delivered'] ?? 'Delivered' }}</span>
                                @elseif($item->status == 4)
                                    <span class="badge fw-semibold text-danger-600 bg-danger-100 px-20 py-9 radius-4 text-white">{{ $lang->data['returned'] ?? 'Returned' }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No orders found for this period.</td>
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
            chart: { type: 'bar', height: 120, stacked: true, stackType: '100%', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '50%' } },
            stroke: { width: 1, colors: ['#fff'] },
            xaxis: { categories: ['Volume'], labels: { show: false }, axisBorder: {show: false}, axisTicks: {show: false} },
            yaxis: { show: false },
            tooltip: { y: { formatter: function(val) { return val + " items" } } },
            fill: { opacity: 1 },
            legend: { position: 'bottom' },
            colors: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1', '#0dcaf0']
        };
        var serviceChart = new ApexCharts(document.querySelector("#serviceChart"), serviceOptions);
        serviceChart.render();
        
        // --- Livewire Re-render on Date Change ---
        Livewire.on('update-order-charts', (event) => {
            const data = event[0];
            if(data && data.services) {
                serviceChart.updateSeries(getServiceSeries(data.services));
            }
        });
    });
</script>
@endpush
