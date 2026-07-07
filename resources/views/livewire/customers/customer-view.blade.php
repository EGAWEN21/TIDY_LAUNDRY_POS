<div class="dashboard-main-body">
    <div class="row gy-4">
        <div class="col-lg-4">
            <div class="user-grid-card position-relative border radius-16 overflow-hidden bg-base h-100">
                <div class="pb-24  mb-24 tw-px-4 tw-mx-0 tw-flex tw-flex-col">
                    <div class=" border-bottom">
                        <div class="tw-flex tw-items-center tw-gap-4 tw-py-2">
                            <div class="tw-size-14 dark:tw-bg-white tw-bg-neutral-300 tw-rounded-full"></div>
                            <div class="tw-flex tw-flex-col">
                                <h6 class="mb-0 mt-16"> {{$customer->name}}</h6>
                                <span class="text-secondary-light mb-16"> {{$customer->email}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-24 tw-gap-6 tw-flex tw-flex-col">
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-info-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="hugeicons:invoice-01" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal"> {{ $lang->data['total_invoices'] ?? 'Total Invoices' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal"> {{$invoice_count}}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-info-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="mdi:dollar" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal">{{ $lang->data['invoice_total'] ?? 'Invoice Total' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal">{{getFormattedCurrency($invoice_amount)}}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-info-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="mdi:dollar" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal">{{ $lang->data['total_payments'] ?? 'Total Payments' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal">{{getFormattedCurrency($payment)}}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-info-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="mdi:dollar" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal">{{ $lang->data['total_balance'] ?? 'Total Balance' }}</h6>
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="text-sm text-secondary-light fw-normal">
                                        @php
                                        $balance_amount = $invoice_amount - $payment;
                                        @endphp
                                        @if ($balance_amount == 0)
                                            {{ getFormattedCurrency($balance_amount) }} {{ 'Cr' }}
                                        @else
                                            @if ($balance_amount < 0)
                                                {{ getFormattedCurrency($balance_amount * -1) }} {{ 'Cr' }}
                                            @else
                                                {{ getFormattedCurrency($balance_amount) }} {{ 'Dr' }}
                                            @endif
                                        @endif
                                    </span>
                                    @if($balance_amount <= 0)
                                        <span class="badge bg-success-100 text-success-600 px-2 py-1 radius-4 tw-text-[0.65rem]">Good Standing</span>
                                    @else
                                        <span class="badge bg-danger-100 text-danger-600 px-2 py-1 radius-4 tw-text-[0.65rem]">Outstanding</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-success-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:phone-outline" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal">{{ $lang->data['phone_number'] ?? 'Phone Number' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal">{{getCountryCode()}} {{$customer->phone ? $customer->phone : '-'}}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-success-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="oui:email" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal"> {{ $lang->data['email'] ?? 'Email' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal">{{$customer->email ? $customer->email : '-'}}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-success-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="tabler:tax" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal">{{ $lang->data['tax_number'] ?? 'Tax Number' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal">{{$customer->tax_number ? $customer->tax_number : '-'}}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-12">
                            <div class="w-40-px h-40-px radius-8 flex-shrink-0 bg-success-50 d-flex justify-content-center align-items-center">
                                <iconify-icon icon="entypo:address" class="text-neutral-900 tw-text-lg"></iconify-icon>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-md mb-0 fw-normal"> {{ $lang->data['address'] ?? 'Address' }}</h6>
                                <span class="text-sm text-secondary-light fw-normal"> {{$customer->address ? $customer->address : '-'}}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body p-24">
                    
                    {{-- KPI Row --}}
                    <div class="row gy-3 mb-24">
                        <div class="col-md-3">
                            <div class="tw-bg-neutral-50 tw-p-3 tw-rounded-xl tw-border tw-border-neutral-100">
                                <div class="tw-text-xs tw-text-neutral-500 tw-font-medium mb-1">Total Orders</div>
                                <div class="tw-text-lg tw-font-semibold tw-text-neutral-800">{{ $invoice_count }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="tw-bg-neutral-50 tw-p-3 tw-rounded-xl tw-border tw-border-neutral-100">
                                <div class="tw-text-xs tw-text-neutral-500 tw-font-medium mb-1">Lifetime Spend</div>
                                <div class="tw-text-lg tw-font-semibold tw-text-neutral-800">{{ getFormattedCurrency($invoice_amount) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="tw-bg-neutral-50 tw-p-3 tw-rounded-xl tw-border tw-border-neutral-100">
                                <div class="tw-text-xs tw-text-neutral-500 tw-font-medium mb-1">Avg Order Value</div>
                                <div class="tw-text-lg tw-font-semibold tw-text-neutral-800">{{ getFormattedCurrency($avg_order_value) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="tw-bg-neutral-50 tw-p-3 tw-rounded-xl tw-border tw-border-neutral-100">
                                <div class="tw-text-xs tw-text-neutral-500 tw-font-medium mb-1">Last Order</div>
                                <div class="tw-text-lg tw-font-semibold tw-text-neutral-800">
                                    @if($last_order_date)
                                        {{ \Carbon\Carbon::parse($last_order_date)->format('M j, Y') }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav border-gradient-tab nav-pills mb-20 d-inline-flex" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                          <button class="nav-link d-flex align-items-center px-24 active" id="pills-edit-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-edit-profile" type="button" role="tab" aria-controls="pills-edit-profile" aria-selected="true">
                          {{ $lang->data['invoices'] ?? 'Invoices' }}
                          </button>
                        </li>
                        <li class="nav-item" role="presentation">
                          <button class="nav-link d-flex align-items-center px-24" id="pills-change-passwork-tab" data-bs-toggle="pill" data-bs-target="#pills-change-passwork" type="button" role="tab" aria-controls="pills-change-passwork" aria-selected="false" tabindex="-1">
                          {{ $lang->data['payments'] ?? 'Payments' }} 
                          </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">   
                        <livewire:customers.partials.customer-invoice :customer="$customer"/>
                        <livewire:customers.partials.customer-payments :customer="$customer"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>