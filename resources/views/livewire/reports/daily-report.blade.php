<div class="dashboard-main-body">
    <!-- Top Filter & Print Row -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
        <div class="tw-flex tw-items-center tw-gap-2">
            <label class="tw-text-sm tw-font-medium">{{$lang->data['date'] ?? 'Date'}}</label>
            <input type="date" class="form-control bg-base h-40-px w-auto" wire:model.live="today">
            <x-report-granularity :lang="$lang" />
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
            @can('report_download')
            <button type="button" wire:click="downloadFile()" class="btn btn-warning-100 text-warning-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:download-bold" class="mr-1"></iconify-icon>
                {{$lang->data['download_report'] ?? 'Data (PDF)'}}
            </button>
            <button type="button" wire:click="downloadCsv()" class="btn btn-primary-100 text-primary-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
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

    <!-- KPI Cards Row 1 (Operations) -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card title="{{$lang->data['orders'] ?? 'Orders Received'}}" value="{{ $new_order }}" icon="akar-icons:cart" color="primary" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['delivered'] ?? 'Orders Delivered'}}" value="{{ $delivered_orders }}" icon="mdi:truck-delivery-outline" color="success" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['item_volume'] ?? 'Items Processed'}}" value="{{ $itemVolume }}" icon="mdi:tshirt-crew-outline" color="info" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['pending_orders'] ?? 'Pending Now'}}" value="{{ $pending_orders }}" icon="mdi:clock-outline" color="warning" />
        </div>
    </div>

    <!-- KPI Cards Row 2 (Money) -->
    <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-4 tw-mb-6 tw-items-center">
        <div class="tw-flex-1 tw-w-full">
            <x-dashboard-card title="{{$lang->data['total_sales'] ?? 'Revenue Billed'}}" value="{{ getFormattedCurrency($total_sales) }}" icon="mdi:cash-register" color="primary" />
        </div>
        <div class="tw-shrink-0">
            <span class="badge bg-warning tw-text-dark tw-px-3 tw-py-2 tw-rounded-full tw-shadow-sm tw-text-sm">
                Collection Gap: {{ getFormattedCurrency($total_sales - $cash_collected) }}
            </span>
        </div>
        <div class="tw-flex-1 tw-w-full">
            <x-dashboard-card title="{{$lang->data['cash_collected'] ?? 'Cash Collected'}}" value="{{ getFormattedCurrency($cash_collected) }}" icon="mdi:cash-multiple" color="success" />
        </div>
        <div class="tw-flex-1 tw-w-full lg:tw-ml-4">
            <x-dashboard-card title="{{$lang->data['total_expense'] ?? 'Total Expenses'}}" value="{{ getFormattedCurrency($total_expense) }}" icon="mdi:cash-minus" color="danger" />
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- 7-Day Trend Chart -->
        <div class="col-12 col-lg-7">
            <x-chart-container title="{{$lang->data['7_day_trend'] ?? '7-Day Inflow Trend'}}">
                <div id="trendChart"></div>
            </x-chart-container>
        </div>
        <!-- Payment Split Chart -->
        <div class="col-12 col-lg-5">
            <x-chart-container title="{{$lang->data['payment_split'] ?? 'Payment Breakdown'}}">
                <div id="paymentChart"></div>
            </x-chart-container>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row g-4 tw-mt-2">
        <div class="col-12 col-lg-6">
            <div class="card basic-data-table h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Delivered & Unpaid Today</h5>
                </div>
                <div class="card-body tw-overflow-x-auto">
                    @if(count($unpaidDeliveries) > 0)
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Order #</th>
                                    <th>Amount Owed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unpaidDeliveries as $unpaid)
                                    <tr>
                                        <td>{{ $unpaid->customer_name }}</td>
                                        <td>{{ $unpaid->phone_number }}</td>
                                        <td>{{ $unpaid->order_number }}</td>
                                        <td class="tw-text-danger tw-font-semibold">{{ getFormattedCurrency($unpaid->amount_owed) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="tw-p-4 tw-text-center tw-text-success tw-font-medium">All paid!</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card basic-data-table h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Overdue Deliveries</h5>
                </div>
                <div class="card-body tw-overflow-x-auto">
                    @if(count($overdueOrders) > 0)
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Order #</th>
                                    <th>Promised Date</th>
                                    <th>Days Overdue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($overdueOrders as $overdue)
                                    <tr>
                                        <td>{{ $overdue->customer_name }}</td>
                                        <td>{{ $overdue->phone_number }}</td>
                                        <td>{{ $overdue->order_number }}</td>
                                        <td>{{ \Carbon\Carbon::parse($overdue->delivery_date)->format('d/m/Y') }}</td>
                                        <td class="tw-text-danger tw-font-semibold">{{ $overdue->days_overdue }} days</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="tw-p-4 tw-text-center tw-text-success tw-font-medium">No overdue orders</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('livewire:initialized', () => {
        // --- 1. Trend Line Chart ---
        var trendOptions = {
            series: [{ name: 'Collected', data: @json($trendData) }],
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [50, 100] } },
            labels: @json($trendLabels),
            colors: ['#0d6efd'],
            dataLabels: { enabled: false },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return "{{ getCurrency() }}" + parseFloat(value).toFixed(2);
                    }
                }
            }
        };
        var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();
        
        // --- 2. Payment Split (Donut Chart) ---
        function getPaymentSeries(paymentData) {
            return paymentData.map(item => parseFloat(item.amount));
        }
        function getPaymentLabels(paymentData) {
            return paymentData.map(item => item.name);
        }

        var initialPaymentData = @json($paymentSplit);
        var paymentSeries = getPaymentSeries(initialPaymentData);
        var paymentLabels = getPaymentLabels(initialPaymentData);

        if(paymentSeries.length === 0) {
            paymentSeries = [1];
            paymentLabels = ['No Payments'];
        }

        var paymentOptions = {
            series: paymentSeries,
            labels: paymentLabels,
            chart: { type: 'donut', height: 300, toolbar: { show: false } },
            tooltip: { y: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } } },
            legend: { position: 'bottom' },
            colors: ['#28a745', '#0dcaf0', '#ffc107', '#dc3545', '#6610f2']
        };
        var paymentChart = new ApexCharts(document.querySelector("#paymentChart"), paymentOptions);
        paymentChart.render();
        
        // --- 3. Livewire Re-render on Date Change ---
        Livewire.on('update-daily-charts', (event) => {
            const data = event[0];
            if(data) {
                // Update Trend Line
                trendChart.updateSeries([{ data: data.data }]);
                trendChart.updateOptions({ labels: data.labels });
                
                // Update Payment Split
                let pSeries = getPaymentSeries(data.payment);
                let pLabels = getPaymentLabels(data.payment);
                if(pSeries.length === 0) {
                    pSeries = [1];
                    pLabels = ['No Payments'];
                }
                paymentChart.updateSeries(pSeries);
                paymentChart.updateOptions({ labels: pLabels });
            }
        });
    });
</script>
@endpush
