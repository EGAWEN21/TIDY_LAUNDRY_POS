<div class="dashboard-main-body">
    <!-- Top Filter & Print Row -->
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
        <div class="tw-flex tw-items-center tw-gap-2">
            <label class="tw-text-sm tw-font-medium">{{$lang->data['date'] ?? 'Date'}}</label>
            <input type="date" class="form-control bg-base h-40-px w-auto" wire:model.live="today">
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
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4 tw-mb-6">
        <div class="col">
            <x-dashboard-card title="{{$lang->data['orders'] ?? 'Orders Today'}}" value="{{ $new_order }}" icon="akar-icons:cart" color="primary" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['delivered'] ?? 'Delivered'}}" value="{{ $delivered_orders }}" icon="mdi:truck-delivery-outline" color="success" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['item_volume'] ?? 'Items Processed'}}" value="{{ $itemVolume }}" icon="mdi:tshirt-crew-outline" color="info" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['total_sales'] ?? 'Total Sales'}}" value="{{ getFormattedCurrency($total_sales) }}" icon="mdi:cash-register" color="success" />
        </div>
        <div class="col">
            <x-dashboard-card title="{{$lang->data['total_expense'] ?? 'Total Expense'}}" value="{{ getFormattedCurrency($total_expense) }}" icon="mdi:cash-minus" color="danger" />
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
        <!-- Payment Split Chart (100% Stacked Bar) -->
        <div class="col-12 col-lg-5">
            <x-chart-container title="{{$lang->data['payment_split'] ?? 'Payment Breakdown'}}">
                <div id="paymentChart"></div>
            </x-chart-container>
        </div>
    </div>
</div>

@push('js')
<script>
    document.addEventListener('livewire:initialized', () => {
        // --- 1. Trend Line Chart ---
        var trendOptions = {
            series: [{ name: 'Orders', data: @json($trendData) }],
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [50, 100] } },
            labels: @json($trendLabels),
            colors: ['#0d6efd'],
            dataLabels: { enabled: false }
        };
        var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();
        
        // --- 2. Payment Split (100% Stacked Horizontal Bar) ---
        // Format initial data
        function getPaymentSeries(paymentData) {
            var series = [];
            if(paymentData.length === 0) {
                return [{name: 'No Payments', data: [0]}];
            }
            paymentData.forEach(function(item) {
                series.push({
                    name: item.name,
                    data: [item.amount]
                });
            });
            return series;
        }

        var paymentOptions = {
            series: getPaymentSeries(@json($paymentSplit)),
            chart: { type: 'bar', height: 150, stacked: true, stackType: '100%', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, barHeight: '50%' } },
            stroke: { width: 1, colors: ['#fff'] },
            xaxis: { categories: ['Payments'], labels: { show: false }, axisBorder: {show: false}, axisTicks: {show: false} },
            yaxis: { show: false },
            tooltip: { y: { formatter: function(val) { return "₦" + val.toFixed(2) } } },
            fill: { opacity: 1 },
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
                paymentChart.updateSeries(getPaymentSeries(data.payment));
            }
        });
    });
</script>
@endpush