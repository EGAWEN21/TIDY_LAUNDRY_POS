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
        <div class="tw-flex tw-items-center tw-gap-2">
            @can('report_download')
            <button type="button" wire:click="downloadFile()" class="btn btn-warning-100 text-warning-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:download-bold" class="mr-1"></iconify-icon>
                {{$lang->data['download_report'] ?? 'Data (PDF)'}}
            </button>
            <button type="button" wire:click="downloadCsv()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2 tw-ml-2">
                <iconify-icon icon="solar:download-bold" class="mr-1"></iconify-icon>
                {{$lang->data['download_csv'] ?? 'Download CSV'}}
            </button>
            @endcan
            <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
                {{$lang->data['print_report'] ?? 'Print Report'}}
            </button>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="tw-flex tw-gap-4 tw-mb-6 tw-border-b">
        <button class="tw-px-4 tw-py-2 tw-font-bold {{ $activeTab == 'financial' ? 'tw-text-primary-600 tw-border-b-2 tw-border-primary-600' : 'tw-text-gray-500' }}" wire:click="$set('activeTab', 'financial')">Financial</button>
        <button class="tw-px-4 tw-py-2 tw-font-bold {{ $activeTab == 'operations' ? 'tw-text-primary-600 tw-border-b-2 tw-border-primary-600' : 'tw-text-gray-500' }}" wire:click="$set('activeTab', 'operations')">Operations</button>
    </div>

    @if($activeTab == 'financial')
    <!-- KPI Cards Row -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-5 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="Revenue Billed" 
                value="{{ getFormattedCurrency($financialKpi['revenue_billed'] ?? 0) }}" 
                icon="mdi:cash-register" 
                color="success" 
                trend="{{ ($financialKpi['growth'] ?? 0) > 0 ? '+' : '' }}{{ $financialKpi['growth'] ?? 0 }}% vs prev"
                :trendUp="($financialKpi['growth'] ?? 0) >= 0" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Cash Collected" 
                value="{{ getFormattedCurrency($financialKpi['cash_collected'] ?? 0) }}" 
                icon="mdi:cash-multiple" 
                color="primary" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Collection Rate %" 
                value="{{ $financialKpi['collection_rate_pct'] ?? 0 }}%" 
                icon="mdi:percent-outline" 
                color="info" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="AOV" 
                value="{{ getFormattedCurrency($financialKpi['aov'] ?? 0) }}" 
                icon="mdi:chart-line-variant" 
                color="warning" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Discount Rate %" 
                value="{{ $financialKpi['discount_rate_pct'] ?? 0 }}%" 
                icon="mdi:ticket-percent-outline" 
                color="danger" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 tw-mb-6">
        <div class="col-lg-6">
            <x-chart-container title="Revenue Trend">
                <div id="trendChart"></div>
            </x-chart-container>
        </div>
        <div class="col-lg-6">
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
                            <th scope="col">Paid</th>
                            <th scope="col">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->orders as $item)
                        <tr>
                            <td><span class="text-primary fw-bold">{{ $item->order_number }}</span></td>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') }}</p></td>
                            <td>
                                <p class="text-sm font-weight-bold tw-text-black mb-0">{{ $item->customer_name }}</p>
                                <span class="text-xs text-muted">{{ $item->phone_number ?? $item->customer_phone }}</span>
                            </td>
                            <td><p class="text-sm mb-0 text-danger">{{ getFormattedCurrency($item->discount) }}</p></td>
                            <td><p class="text-sm font-weight-bold text-success mb-0">{{ getFormattedCurrency($item->total) }}</p></td>
                            <td><p class="text-sm font-weight-bold text-primary mb-0">{{ getFormattedCurrency($item->paid ?? 0) }}</p></td>
                            <td><p class="text-sm font-weight-bold text-warning mb-0">{{ getFormattedCurrency($item->outstanding ?? 0) }}</p></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No sales found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4 tw-px-4">
                    {{ $this->orders->links() }}
                </div>
            </div>
        </div>
    </div>
    @elseif($activeTab == 'operations')
    <!-- KPI Cards Row -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-5 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card 
                title="Total Orders" 
                value="{{ $operationalKpi['total_orders'] ?? 0 }}" 
                icon="akar-icons:cart" 
                color="primary" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Avg TAT (days)" 
                value="{{ $operationalKpi['avg_tat'] ?? 0 }}" 
                icon="mdi:clock-outline" 
                color="info" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="On-Time %" 
                value="{{ $operationalKpi['on_time_pct'] ?? 0 }}%" 
                icon="mdi:check-circle-outline" 
                color="success" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Return Rate" 
                value="{{ $operationalKpi['return_rate'] ?? 0 }}%" 
                icon="mdi:keyboard-return" 
                color="warning" />
        </div>
        <div class="col">
            <x-dashboard-card 
                title="Overdue Count" 
                value="{{ $operationalKpi['overdue_count'] ?? 0 }}" 
                icon="mdi:alert-circle-outline" 
                color="danger" />
        </div>
    </div>

    <!-- Status Filter & Chart -->
    <div class="row g-4 tw-mb-6">
        <div class="col-12">
            <div class="tw-mb-4 tw-w-64">
                <label class="tw-text-sm tw-font-medium">Filter by Status</label>
                <select class="form-control" wire:model.live="status">
                    <option value="-1">All Statuses</option>
                    <option value="0">Pending</option>
                    <option value="1">Processing</option>
                    <option value="2">Ready</option>
                    <option value="3">Delivered</option>
                    <option value="4">Returned</option>
                </select>
            </div>
            <x-chart-container title="Order Pipeline">
                <div id="pipelineChart"></div>
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
                            <th scope="col">Status</th>
                            <th scope="col">Promised Delivery</th>
                            <th scope="col">Late</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->orders as $item)
                        <tr>
                            <td><span class="text-primary fw-bold">{{ $item->order_number }}</span></td>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->order_date)->format('d/m/Y') }}</p></td>
                            <td>
                                <p class="text-sm font-weight-bold tw-text-black mb-0">{{ $item->customer_name }}</p>
                                <span class="text-xs text-muted">{{ $item->phone_number ?? $item->customer_phone }}</span>
                            </td>
                            <td>
                                @if($item->status == 0)
                                    <span class="badge bg-warning-100 text-warning-600 radius-8">Pending</span>
                                @elseif($item->status == 1)
                                    <span class="badge bg-primary-100 text-primary-600 radius-8">Processing</span>
                                @elseif($item->status == 2)
                                    <span class="badge bg-info-100 text-info-600 radius-8">Ready</span>
                                @elseif($item->status == 3)
                                    <span class="badge bg-success-100 text-success-600 radius-8">Delivered</span>
                                @elseif($item->status == 4)
                                    <span class="badge bg-danger-100 text-danger-600 radius-8">Returned</span>
                                @endif
                            </td>
                            <td><p class="text-sm mb-0">{{ \Carbon\Carbon::parse($item->delivery_date)->format('d/m/Y') }}</p></td>
                            <td>
                                @if(in_array($item->status, [0,1,2]) && \Carbon\Carbon::parse($item->delivery_date)->endOfDay()->isPast())
                                    <span class="badge bg-danger-100 text-danger-600 radius-8">Yes</span>
                                @else
                                    <span class="badge bg-success-100 text-success-600 radius-8">No</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No orders found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4 tw-px-4">
                    {{ $this->orders->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('js')
<script>
    document.addEventListener('livewire:initialized', () => {
        let serviceChart, trendChart, pipelineChart;

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

        function initCharts(data) {
            if (data.activeTab === 'financial') {
                if (document.querySelector("#serviceChart") && document.querySelector("#trendChart")) {
                    let serviceOptions = {
                        series: getServiceSeries(data.services),
                        chart: { type: 'bar', height: 180, stacked: true, stackType: '100%', toolbar: { show: false } },
                        plotOptions: { bar: { horizontal: true, barHeight: '50%' } },
                        stroke: { width: 1, colors: ['#fff'] },
                        xaxis: { categories: ['Revenue'], labels: { show: false }, axisBorder: {show: false}, axisTicks: {show: false} },
                        yaxis: { show: false },
                        tooltip: { y: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } } },
                        fill: { opacity: 1 },
                        legend: { position: 'bottom' },
                        colors: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1', '#0dcaf0']
                    };
                    if (serviceChart) serviceChart.destroy();
                    serviceChart = new ApexCharts(document.querySelector("#serviceChart"), serviceOptions);
                    serviceChart.render();

                    let trendOptions = {
                        series: [{ name: 'Revenue', data: data.trendData || [] }],
                        chart: { type: 'line', height: 300, toolbar: { show: false } },
                        xaxis: { categories: data.trendLabels || [] },
                        stroke: { curve: 'smooth', width: 2 },
                        colors: ['#20c997']
                    };
                    if (trendChart) trendChart.destroy();
                    trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
                    trendChart.render();
                }
            } else if (data.activeTab === 'operations') {
                if (document.querySelector("#pipelineChart")) {
                    let pipelineOptions = {
                        series: data.pipelineData || [],
                        chart: { type: 'bar', height: 300, stacked: false, toolbar: { show: false } },
                        plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
                        xaxis: { categories: ['Orders Pipeline'] },
                        colors: ['#ffc107', '#0d6efd', '#0dcaf0', '#20c997', '#dc3545']
                    };
                    if (pipelineChart) pipelineChart.destroy();
                    pipelineChart = new ApexCharts(document.querySelector("#pipelineChart"), pipelineOptions);
                    pipelineChart.render();
                }
            }
        }

        setTimeout(() => {
            initCharts({
                activeTab: @json($activeTab),
                services: @json($serviceBreakdown),
                trendLabels: @json($trendLabels),
                trendData: @json($trendData),
                pipelineData: @json($pipelineData)
            });
        }, 100);
        
        Livewire.on('update-sales-charts', (event) => {
            const data = event[0];
            if(data) {
                setTimeout(() => {
                    initCharts(data);
                }, 100);
            }
        });
    });
</script>
@endpush
