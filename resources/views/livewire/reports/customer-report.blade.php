<div class="dashboard-main-body">

    <!-- KPI Summary Cards -->
    <div class="row gy-4 mb-4">
        <div class="col-xxl-3 col-sm-6">
            <x-dashboard-card
                title="Total Customers"
                value="{{ $kpiSummary['total_customers'] ?? 0 }}"
                icon="solar:users-group-rounded-bold-duotone"
                color="primary"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-dashboard-card
                title="At-Risk (Lapsing + Dormant)"
                value="{{ $kpiSummary['at_risk_count'] ?? 0 }}"
                icon="solar:danger-triangle-bold-duotone"
                color="warning"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-dashboard-card
                title="Total Outstanding Debt"
                value="{{ getFormattedCurrency($kpiSummary['total_outstanding'] ?? 0) }}"
                icon="solar:wallet-bold-duotone"
                color="danger"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-dashboard-card
                title="Avg Lifetime Value"
                value="{{ getFormattedCurrency($kpiSummary['avg_ltv'] ?? 0) }}"
                icon="solar:wad-of-money-bold-duotone"
                color="success"
            />
        </div>
    </div>

    <!-- Acquisition Trend Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card h-100 p-0 radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0">New Customer Acquisition (Last 12 Months)</h6>
                </div>
                <div class="card-body p-24">
                    <div id="acquisitionChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="tw-py-1.5 tw-px-3 bg-base d-flex align-items-center flex-wrap gap-3 justify-content-between">
            <div class="tw-flex tw-items-center gap-4">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-1 tw-flex-col">
                        <span class="fw-medium">{{ $lang->data['status'] ?? 'Customer Status' }}</span>
                        <select class="form-select form-select-sm bg-base h-40-px w-auto" wire:model.live="statusFilter">
                            <option class="select-box" value="0">All</option>
                            <option class="select-box" value="1">New</option>
                            <option class="select-box" value="2">Active</option>
                            <option class="select-box" value="3">Lapsing</option>
                            <option class="select-box" value="4">Dormant</option>
                            <option class="select-box" value="5">Lost</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="tw-flex tw-items-center gap-2">
                <button type="button" wire:click="downloadCsv" class="btn btn-primary-100 text-primary-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                    <iconify-icon icon="solar:download-minimalistic-bold" class="mr-1"></iconify-icon>
                    Download CSV
                </button>
                <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                    <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
                    {{$lang->data['print_report'] ?? 'Print Report'}}
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm tw-min-h-[calc(100vh-16rem)]">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>{{$lang->data['sl'] ?? 'S/N'}}</th>
                            <th>{{$lang->data['customer'] ?? 'Customer'}}</th>
                            <th>{{$lang->data['registered'] ?? 'Registered'}}</th>
                            <th>{{$lang->data['orders'] ?? 'Orders'}}</th>
                            <th>{{$lang->data['lifetime_spend'] ?? 'Lifetime Spend'}}</th>
                            <th>{{$lang->data['spend_30'] ?? 'Spend (30 Days)'}}</th>
                            <th>{{$lang->data['aov'] ?? 'Avg Order Val'}}</th>
                            <th>{{$lang->data['outstanding'] ?? 'Outstanding'}}</th>
                            <th>{{$lang->data['last_visit'] ?? 'Last Visit'}}</th>
                            <th>{{$lang->data['status'] ?? 'Status'}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->customersData as $row)
                        <tr wire:click="toggleCustomerOrders({{ $row['id'] }})" class="cursor-pointer" style="cursor: pointer;">
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    {{ $loop->iteration }}
                                    @if($expandedCustomerId == $row['id'])
                                        <iconify-icon icon="solar:alt-arrow-up-bold" class="text-muted"></iconify-icon>
                                    @else
                                        <iconify-icon icon="solar:alt-arrow-down-bold" class="text-muted"></iconify-icon>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <p class="text-sm font-weight-bold tw-text-black mb-0">{{$row['name']}}</p>
                                <span class="text-xs text-muted">{{$row['phone']}}</span>
                            </td>
                            <td><p class="text-sm mb-0">{{$row['registration_date']}}</p></td>
                            <td><p class="text-sm mb-0">{{$row['total_orders']}}</p></td>
                            <td><p class="text-sm font-weight-bold text-success mb-0">{{getFormattedCurrency($row['total_spend'])}}</p></td>
                            <td>
                                <div class="d-flex align-items-center gap-1" title="Previous Month: {{ getFormattedCurrency($row['spend_prev_month']) }}">
                                    <p class="text-sm mb-0">{{getFormattedCurrency($row['spend_30'])}}</p>
                                    @if($row['spend_trend_up'])
                                        <iconify-icon icon="solar:course-up-bold" class="text-success text-sm"></iconify-icon>
                                    @else
                                        <iconify-icon icon="solar:course-down-bold" class="text-danger text-sm"></iconify-icon>
                                    @endif
                                </div>
                            </td>
                            <td><p class="text-sm mb-0">{{getFormattedCurrency($row['aov'])}}</p></td>
                            <td>
                                @if($row['outstanding'] > 0)
                                    <p class="text-sm font-weight-bold text-danger mb-0">{{getFormattedCurrency($row['outstanding'])}}</p>
                                @else
                                    <p class="text-sm text-muted mb-0">{{ getFormattedCurrency(0) }}</p>
                                @endif
                            </td>
                            <td><p class="text-sm mb-0">{{$row['last_visit']}}</p></td>
                            <td>
                                @if($row['status_code'] == 1)
                                    <span class="badge fw-semibold text-primary-600 bg-primary-100 px-20 py-9 radius-4">New</span>
                                @elseif($row['status_code'] == 2)
                                    <span class="badge fw-semibold text-success-600 bg-success-100 px-20 py-9 radius-4">Active</span>
                                @elseif($row['status_code'] == 3)
                                    <span class="badge fw-semibold text-warning-600 bg-warning-100 px-20 py-9 radius-4">Lapsing</span>
                                @elseif($row['status_code'] == 4)
                                    <span class="badge fw-semibold text-orange-600 bg-orange-100 px-20 py-9 radius-4">Dormant</span>
                                @else
                                    <span class="badge fw-semibold text-danger-600 bg-danger-100 px-20 py-9 radius-4">Lost</span>
                                @endif
                            </td>
                        </tr>
                        @if($expandedCustomerId == $row['id'])
                        <tr>
                            <td colspan="10" class="p-0 bg-light">
                                <div class="p-4 border-bottom border-top" style="background-color: #f8f9fa;">
                                    <h6 class="mb-3 text-sm font-weight-bold">Order History for {{$row['name']}}</h6>
                                    <table class="table table-sm bordered-table mb-0 bg-white radius-8 overflow-hidden">
                                        <thead class="bg-base">
                                            <tr>
                                                <th class="text-xs">Order#</th>
                                                <th class="text-xs">Date</th>
                                                <th class="text-xs">Delivery Date</th>
                                                <th class="text-xs">Services</th>
                                                <th class="text-xs">Status</th>
                                                <th class="text-xs">Total</th>
                                                <th class="text-xs">Paid</th>
                                                <th class="text-xs">Outstanding</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $orders = $this->customerOrders;
                                                $tOrders = count($orders);
                                                $tSpend = 0;
                                                $tOutstanding = 0;
                                            @endphp
                                            @forelse($orders as $o)
                                            @php
                                                $tSpend += $o->total;
                                                $tOutstanding += $o->outstanding;
                                            @endphp
                                            <tr>
                                                <td class="text-sm font-weight-bold">{{ $o->order_number }}</td>
                                                <td class="text-sm">{{ \Carbon\Carbon::parse($o->order_date)->format('d/m/Y') }}</td>
                                                <td class="text-sm">{{ $o->delivery_date ? \Carbon\Carbon::parse($o->delivery_date)->format('d/m/Y') : 'N/A' }}</td>
                                                <td class="text-sm text-wrap" style="max-width: 200px;">{{ $o->services }}</td>
                                                <td>
                                                    @if($o->status == 0)
                                                        <span class="badge fw-semibold px-20 py-9 radius-4 text-secondary-600 bg-secondary-100">Pending</span>
                                                    @elseif($o->status == 1)
                                                        <span class="badge fw-semibold px-20 py-9 radius-4 text-primary-600 bg-primary-100">Processing</span>
                                                    @elseif($o->status == 2)
                                                        <span class="badge fw-semibold px-20 py-9 radius-4" style="background-color: #f3e8ff; color: #7e22ce;">Ready</span>
                                                    @elseif($o->status == 3)
                                                        <span class="badge fw-semibold px-20 py-9 radius-4 text-success-600 bg-success-100">Delivered</span>
                                                    @elseif($o->status == 4)
                                                        <span class="badge fw-semibold px-20 py-9 radius-4 text-danger-600 bg-danger-100">Returned</span>
                                                    @endif
                                                </td>
                                                <td class="text-sm text-success font-weight-bold">{{ getFormattedCurrency($o->total) }}</td>
                                                <td class="text-sm">{{ getFormattedCurrency($o->paid) }}</td>
                                                <td class="text-sm">
                                                    @if($o->outstanding > 0)
                                                        <span class="text-danger font-weight-bold">{{ getFormattedCurrency($o->outstanding) }}</span>
                                                    @else
                                                        <span class="text-muted">{{ getFormattedCurrency(0) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-3 text-muted text-sm">No orders found.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        @if($tOrders > 0)
                                        <tfoot class="bg-base">
                                            <tr>
                                                <th colspan="5" class="text-end text-sm">Summary ({{ $tOrders }} Orders):</th>
                                                <th class="text-sm text-success font-weight-bold">{{ getFormattedCurrency($tSpend) }}</th>
                                                <th></th>
                                                <th class="text-sm">
                                                    @if($tOutstanding > 0)
                                                        <span class="text-danger font-weight-bold">{{ getFormattedCurrency($tOutstanding) }}</span>
                                                    @else
                                                        <span class="text-muted">{{ getFormattedCurrency(0) }}</span>
                                                    @endif
                                                </th>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No customers found for this filter.</td>
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
        let trendData = @json($this->acquisitionTrend);
        let options = {
            series: [{
                name: 'New Customers',
                data: trendData.counts
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: trendData.months,
            },
            colors: ['#487fff']
        };

        let chart = new ApexCharts(document.querySelector("#acquisitionChart"), options);
        chart.render();
    });
</script>
@endpush
