<div class="dashboard-main-body">
    <!-- Charts Row: Global Receivables Ageing -->
    <div class="row g-4 tw-mb-6">
        <div class="col-12">
            <x-chart-container title="{{$lang->data['global_receivables_ageing'] ?? 'Global Receivables Ageing (All Customers)'}}">
                <div id="ageingChart"></div>
            </x-chart-container>
        </div>
    </div>

    <!-- Customer Ledger Filter -->
    <div class="card p-4 radius-12 border-0 shadow-sm tw-bg-white tw-mb-6">
        <h6 class="tw-font-bold tw-mb-4">{{$lang->data['customer_ledger'] ?? 'Customer Ledger Lookup'}}</h6>
        <div class="row gy-3">
            <div class="col-12 col-md-4">
                <label class="form-label">{{$lang->data['select_customer'] ?? 'Search Customer'}}</label>
                <div class="position-relative">
                    <input type="text" class="form-control" wire:model.live="customer_query" placeholder="{{$lang->data['search_customer'] ?? 'Type name or phone...'}}">
                    @if(count($customers) > 0)
                    <div class="position-absolute w-100 bg-white border rounded shadow-sm z-index-10 mt-1">
                        <ul class="list-unstyled mb-0 max-h-200 overflow-auto">
                            @foreach($customers as $c)
                            <li class="p-2 border-bottom hover-bg-light cursor-pointer" wire:click="selectCustomer({{$c->id}})">
                                <strong>{{$c->name}}</strong> ({{$c->phone}})
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @if($selected_customer)
                    <div class="mt-2 text-success fw-bold">
                        <iconify-icon icon="mdi:check-circle"></iconify-icon>
                        Selected: {{ $selected_customer->name }}
                    </div>
                @endif
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">{{$lang->data['from'] ?? 'From Date'}}</label>
                <input type="date" class="form-control" wire:model.defer="start_date">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">{{$lang->data['to'] ?? 'To Date'}}</label>
                <input type="date" class="form-control" wire:model.defer="end_date">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" wire:click="getData()">
                    {{$lang->data['get_report'] ?? 'Get Report'}}
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    @if(count($this->data) > 0)
    <div class="card h-100 p-0 radius-12">
        <div class="tw-p-4 tw-flex tw-justify-end">
            <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                <iconify-icon icon="solar:printer-bold"></iconify-icon> {{$lang->data['print_ledger'] ?? 'Print Ledger'}}
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ $lang->data['date'] ?? 'Date' }}</th>
                            <th>{{ $lang->data['type'] ?? 'Type' }}</th>
                            <th class="text-danger">{{ $lang->data['debit'] ?? 'Debit (Order)' }}</th>
                            <th class="text-success">{{ $lang->data['credit'] ?? 'Credit (Payment)' }}</th>
                            <th>{{ $lang->data['balance'] ?? 'Running Balance' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = $this->firstData['debits'] - $this->firstData['credits'];
                        @endphp
                        <tr class="bg-light">
                            <td colspan="4" class="text-end fw-bold">Opening Balance:</td>
                            <td class="fw-bold {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ getFormattedCurrency(abs($runningBalance)) }} {{ $runningBalance > 0 ? '(Dr)' : '(Cr)' }}
                            </td>
                        </tr>
                        @foreach($this->data as $row)
                        @php
                            if($row['type'] == 'debit') {
                                $runningBalance += $row['total'];
                            } else {
                                $runningBalance -= $row['received_amount'];
                            }
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td>
                                @if($row['type'] == 'debit')
                                    <span class="badge bg-danger-100 text-danger-600">Order #{{ $row['order_number'] }}</span>
                                @else
                                    <span class="badge bg-success-100 text-success-600">Payment</span>
                                @endif
                            </td>
                            <td class="text-danger">{{ $row['type'] == 'debit' ? getFormattedCurrency($row['total']) : '-' }}</td>
                            <td class="text-success">{{ $row['type'] == 'credit' ? getFormattedCurrency($row['received_amount']) : '-' }}</td>
                            <td class="fw-bold {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ getFormattedCurrency(abs($runningBalance)) }} {{ $runningBalance > 0 ? '(Dr)' : '(Cr)' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@push('js')
<script>
    document.addEventListener('livewire:initialized', () => {
        // --- Receivables Ageing Chart (Bar Chart) ---
        var ageingOptions = {
            series: [{
                name: 'Outstanding Balance',
                data: @json($ageingData)
            }],
            chart: { type: 'bar', height: 250, toolbar: { show: false } },
            plotOptions: {
                bar: { borderRadius: 4, horizontal: false, columnWidth: '40%', distributed: true }
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days (Critical)'],
            },
            yaxis: {
                title: { text: 'Amount (₦)' }
            },
            colors: ['#28a745', '#ffc107', '#fd7e14', '#dc3545'],
            tooltip: {
                y: { formatter: function (val) { return "₦" + val.toFixed(2) } }
            },
            legend: { show: false }
        };
        var ageingChart = new ApexCharts(document.querySelector("#ageingChart"), ageingOptions);
        ageingChart.render();
    });
</script>
@endpush