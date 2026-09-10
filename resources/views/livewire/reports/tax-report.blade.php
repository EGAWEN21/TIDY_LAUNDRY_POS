<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12">
        <div class="tw-py-1.5 tw-px-3 bg-base d-flex align-items-center flex-wrap gap-3 justify-content-between">
            <div class="tw-flex  tw-items-center gap-4">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="d-flex  gap-1 tw-flex-col">
                        <span class="fw-medium">{{ $lang->data['start_date'] ?? 'Start Date' }}</span>
                        <input type="date" class="form-control bg-base h-40-px w-auto" wire:model.live="from_date">
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="d-flex  gap-1 tw-flex-col">
                        <span class="fw-medium">{{ $lang->data['end_date'] ?? 'End Date' }}</span>
                        <input type="date" class="form-control bg-base h-40-px w-auto" wire:model.live="to_date">
                    </div>
                </div>
                <x-report-granularity :lang="$lang" />
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="d-flex  gap-1 tw-flex-col">
                        <span class="fw-medium">{{ $lang->data['filter'] ?? 'Filter' }}</span>
                        <select class="form-select form-select-sm bg-base h-40-px w-auto" wire:model.live="category">
                            <option class="select-box" value="1">{{ $lang->data['sales'] ?? 'Sales' }}</option>
                            <option class="select-box" value="2">{{ $lang->data['expense'] ?? 'Expense' }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4 tw-mb-6 tw-mt-4">
            <div class="col">
                <x-dashboard-card 
                    title="{{ $lang->data['tax_collected'] ?? 'Tax Collected (Sales)' }}" 
                    value="{{ getFormattedCurrency($salesTaxTotal) }}" 
                    icon="mdi:cash-plus" 
                    color="success" />
            </div>
            <div class="col">
                <x-dashboard-card 
                    title="{{ $lang->data['tax_on_expenses'] ?? 'Tax on Expenses' }}" 
                    value="{{ getFormattedCurrency($expenseTaxTotal) }}" 
                    icon="mdi:cash-minus" 
                    color="danger" />
            </div>
            <div class="col">
                <x-dashboard-card 
                    title="{{ $lang->data['net_tax_due'] ?? 'Net Tax Due' }}" 
                    value="{{ getFormattedCurrency($salesTaxTotal - $expenseTaxTotal) }}" 
                    icon="mdi:scale-balance" 
                    color="{{ ($salesTaxTotal - $expenseTaxTotal) >= 0 ? 'primary' : 'warning' }}" />
            </div>
        </div>

        @if($category == 1 && count($rateBands) > 0)
        <div class="card h-100 p-0 radius-12 tw-mb-6">
            <div class="card-body p-0">
                <div class="tw-px-4 tw-py-3">
                    <h6 class="fw-bold tw-text-gray-700">{{ $lang->data['tax_by_rate'] ?? 'Tax Summary by Rate' }}</h6>
                </div>
                <div class="table-responsive">
                    <table class="table bordered-table sm-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ $lang->data['tax_rate'] ?? 'Tax Rate' }}</th>
                                <th>{{ $lang->data['transactions'] ?? '# Transactions' }}</th>
                                <th>{{ $lang->data['taxable_amount'] ?? 'Taxable Amount' }}</th>
                                <th>{{ $lang->data['tax_collected'] ?? 'Tax Collected' }}</th>
                                <th>{{ $lang->data['total_amount'] ?? 'Total' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rateBands as $band)
                            <tr>
                                <td><span class="badge bg-primary-100 text-primary-600 px-12 py-6 radius-4">{{ $band['rate'] }}%</span></td>
                                <td>{{ $band['count'] }}</td>
                                <td>{{ getFormattedCurrency($band['taxable']) }}</td>
                                <td>{{ getFormattedCurrency($band['tax']) }}</td>
                                <td class="fw-bold">{{ getFormattedCurrency($band['total']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>{{ $lang->data['total'] ?? 'Total' }}</td>
                                <td>{{ collect($rateBands)->sum('count') }}</td>
                                <td>{{ getFormattedCurrency($salesTaxableTotal) }}</td>
                                <td>{{ getFormattedCurrency($salesTaxTotal) }}</td>
                                <td>{{ getFormattedCurrency($salesTotal) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="card-body p-0">
            <div class="table-responsive scroll-sm tw-min-h-[calc(100vh-16rem)]">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th class="">#</th>
                            <th class="">{{ $lang->data['date'] ?? 'Date' }}</th>
                            <th class="">{{ $lang->data['particulars'] ?? 'Particulars' }} #</th>
                            <th class="">{{ $lang->data['before_tax'] ?? 'Before Tax' }}</th>
                            <th class="">{{ $lang->data['tax_amount'] ?? 'Tax Amount' }}</th>
                            <th class="">{{ $lang->data['total_amount'] ?? 'Total Amount' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $index => $row)
                        <tr>
                            <td><p>{{ $index + 1 }}</p></td>
                            <td><p>
                                @if($category == 1)
                                    {{ \Carbon\Carbon::parse($row->order_date)->format('d/m/Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($row->expense_date)->format('d/m/Y') }}
                                @endif
                            </p></td>
                            <td><p><span class="font-weight-bold">
                                @if($category == 1)
                                    {{ $row->order_number }}
                                @else
                                    {{ $row->expenseCategory->expense_category_name ?? '' }}
                                @endif
                            </span></p></td>
                            <td><p>{{ getFormattedCurrency($row->computed_before_tax) }}</p></td>
                            <td><p>{{ getFormattedCurrency($row->computed_tax_amount) }}</p></td>
                            <td><p>
                                @if($category == 1)
                                    {{ getFormattedCurrency($row->total) }}
                                @else
                                    {{ getFormattedCurrency($row->computed_total ?? ($row->computed_before_tax + $row->computed_tax_amount)) }}
                                @endif
                            </p></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="tw-flex tw-items-center tw-justify-between tw-w-full tw-p-2 tw-gap-2">
                <div class="tw-flex tw-items-center  tw-gap-4">
                    <div class="">{{$lang->data['total_amount'] ?? 'Total Amount'}}: <span class="tw-font-bold">
                        @if($category==1) {{ getFormattedCurrency($salesTotal) }} @else {{ getFormattedCurrency($expenseTotal) }} @endif
                    </span></div>
                    <div class="">{{$lang->data['total_tax_amount'] ?? 'Total Tax Amount'}}: <span class="tw-font-bold">
                        @if($category==1) {{ getFormattedCurrency($salesTaxTotal) }} @else {{ getFormattedCurrency($expenseTaxTotal) }} @endif
                    </span></div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                    @can('report_download')
                    <button type="button"
                        class="btn btn-warning-100 text-warning-600 radius-8 px-16 py-9" wire:click="downloadFile()">{{$lang->data['download_report'] ?? 'Download Report'}}</button>
                    <button type="button"
                        class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-ml-2" wire:click="downloadCsv()">{{$lang->data['download_csv'] ?? 'Download CSV'}}</button>
                    @endcan
                    @can('report_print')
                    <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                        <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
                        {{$lang->data['print_report'] ?? 'Print Report'}}
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
