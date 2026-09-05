<div class="dashboard-main-body">
    {{-- Back button --}}
    <div class="tw-mb-4">
        <a href="{{ route('orders.requests') }}" class="btn btn-outline-primary-600 radius-8 px-20 py-6 d-inline-flex align-items-center gap-2 text-sm">
            <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            {{ $lang->data['back'] ?? 'Back' }}
        </a>
    </div>

    {{-- Rejected banner --}}
    @if($request->status == 2)
        <div class="tw-bg-danger-50 tw-border tw-border-danger-200 tw-rounded-lg tw-p-4 tw-mb-4 tw-flex tw-justify-between tw-items-start">
            <div>
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-danger-700 tw-font-bold tw-text-sm tw-mb-1">
                    <iconify-icon icon="lucide:x-circle" class="tw-text-lg"></iconify-icon>
                    {{ $lang->data['rejected'] ?? 'Rejected' }}
                </div>
                @if($request->rejection_reason || $request->rejection_note)
                    <div class="tw-text-danger-600 tw-text-sm">
                        {{ $lang->data['reason'] ?? 'Reason' }}: {{ $request->rejection_reason ?? $request->rejection_note }}
                    </div>
                @endif
            </div>
            @if(Auth::id() == $request->created_by || Auth::user()->hasPermission('edit_pending_requests'))
                <a href="{{ route('orders.requests.edit', $request->id) }}" class="btn btn-danger btn-sm radius-8 px-12 py-8 d-inline-flex align-items-center gap-2 text-sm">
                    <iconify-icon icon="lucide:edit" class="text-lg"></iconify-icon>
                    {{ $lang->data['edit'] ?? 'Edit to Resubmit' }}
                </a>
            @endif
        </div>
    @endif

    <div class="tw-flex tw-gap-4 lg:tw-flex-row tw-flex-col">
        {{-- Main content card --}}
        <div class="card h-100 p-0 radius-12 tw-w-full">
            <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                <div class="tw-flex tw-flex-col tw-text-sm">
                    <div class="text-lg tw-font-medium text-primary-light">
                        {{ $sitename }}
                    </div>
                    <div class="tw-flex tw-flex-col tw-mt-2">
                        <div>{{ $phone ? getCountryCode() : '' }}{{ (int)$phone }}</div>
                        <div>{{ $store_email }}</div>
                        <div>{{ $address }} - {{ $zipcode }}</div>
                        <div class="tw-mt-2">{{ $lang->data['tax'] ?? 'TAX' }}: {{ $tax_number }}</div>
                    </div>
                </div>
                <div class="tw-flex tw-flex-col tw-text-sm tw-items-end">
                    <div class="tw-flex tw-flex-col tw-mt-2 tw-items-end">
                        <div class="text-neutral-600">
                            {{ $lang->data['request_number'] ?? 'Request Number' }} : <span class="tw-font-medium text-primary-light">#{{ $request->request_number }}</span>
                        </div>
                        <div class="text-neutral-600">
                            {{ $lang->data['order_date'] ?? 'Order Date' }} : <span class="tw-font-medium text-primary-light">{{ \Carbon\Carbon::parse($payload['order_date'] ?? now())->format('d/m/Y') }}</span>
                        </div>
                        <div class="text-neutral-600">
                            {{ $lang->data['delivery_date'] ?? 'Delivery Date' }} : <span class="tw-font-medium text-primary-light">{{ \Carbon\Carbon::parse($payload['delivery_date'] ?? now())->format('d/m/Y') }}</span>
                        </div>
                        <div class="tw-mt-2 tw-flex tw-items-center tw-gap-2">
                            <div>{{ $lang->data['status'] ?? 'Status' }} :</div>
                            @if($request->status == 0)
                                <span class="badge bg-warning-600 text-warning-600 bg-opacity-20">{{ $lang->data['pending_approval'] ?? 'Pending Approval' }}</span>
                            @elseif($request->status == 2)
                                <span class="badge bg-danger-600 text-danger-600 bg-opacity-20">{{ $lang->data['rejected'] ?? 'Rejected' }}</span>
                            @endif
                        </div>
                        <div class="text-neutral-600 tw-mt-1">
                            {{ $lang->data['created_by'] ?? 'Created By' }} : <span class="tw-font-medium text-primary-light">{{ $request->user->name ?? 'Unknown' }}</span>
                        </div>
                        <div class="text-neutral-600">
                            {{ $lang->data['created_at'] ?? 'Created At' }} : <span class="tw-font-medium text-primary-light">{{ $request->created_at->format('d/m/Y h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-24">
                {{-- Items table --}}
                <div class="table-responsive scroll-sm tw-w-full tw-overflow-x-auto custom-scroll">
                    <table class="table bordered-table sm-table mb-0 tw-whitespace-nowrap">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col" class="tw-sticky tw-left-0 tw-bg-white tw-z-10 tw-shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">{{ $lang->data['service_name'] ?? 'Service Name' }}</th>
                                <th scope="col">{{ $lang->data['color'] ?? 'Color' }}</th>
                                <th scope="col">{{ $lang->data['rate'] ?? 'Rate' }}</th>
                                <th scope="col">{{ $lang->data['qty'] ?? 'QTY' }}</th>
                                <th scope="col">{{ $lang->data['total'] ?? 'Total' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($details as $index => $item)
                                <tr class="tw-text-sm">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="tw-sticky tw-left-0 tw-bg-white tw-z-10 tw-shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                        <div class="tw-flex tw-gap-4 tw-min-w-[12rem]">
                                            <div class="tw-w-10 tw-aspect-square tw-shrink-0 tw-flex tw-items-center tw-justify-center tw-bg-neutral-50 tw-rounded-md">
                                                @if($item['service_icon'] && str_contains($item['service_icon'], ':'))
                                                    <iconify-icon icon="{{ $item['service_icon'] }}" class="tw-text-2xl text-primary"></iconify-icon>
                                                @elseif($item['service_icon'])
                                                    <img src="{{ asset('assets/img/service-icons/' . $item['service_icon']) }}" class="tw-h-full tw-w-full tw-object-contain tw-rounded-md" alt="">
                                                @else
                                                    <iconify-icon icon="tabler:package" class="tw-text-2xl text-primary"></iconify-icon>
                                                @endif
                                            </div>
                                            <div class="tw-flex tw-flex-col">
                                                <p class="tw-text-black">{{ $item['service_name'] }}</p>
                                                <p class="tw-text-gray-600 tw-text-xs">[{{ $item['type_name'] }}]</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-primary">
                                        @if($item['color_code'] != "")
                                            <div class="tw-size-6 tw-rounded-lg tw-border tw-border-neutral-200" style="background-color: {{ $item['color_code'] }}"></div>
                                        @else
                                            <div class="tw-size-6 tw-rounded-lg tw-bg-black tw-border tw-border-neutral-200"></div>
                                        @endif
                                    </td>
                                    <td class="text-primary">{{ getFormattedCurrency($item['price']) }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td class="text-primary">{{ getFormattedCurrency($item['total']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ $lang->data['no_items'] ?? 'No items found' }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="tw-flex tw-flex-col">
                    <div class="tw-flex tw-justify-between tw-items-start tw-mt-6">
                        {{-- Customer info --}}
                        <div class="tw-flex tw-flex-col">
                            <div>{{ $lang->data['invoice_to'] ?? 'Invoice To' }}</div>
                            <div class="tw-mt-2 tw-font-medium tw-text-sm">
                                {{ $customer->name ?? ($payload['customer_name'] ?? 'Walk-In Customer') }}
                            </div>
                            <div class="tw-text-sm">
                                {{ $customer && $customer->phone ? getCountryCode() : '' }} {{ $customer && $customer->phone ? (int)$customer->phone : ($payload['phone_number'] ?? '') }}
                            </div>
                            <div class="tw-text-sm">
                                {{ $customer->email ?? '' }}
                            </div>
                            <div class="tw-text-sm">
                                {{ $customer->address ?? '' }}
                            </div>
                            @if($customer && $customer->tax_number)
                                <div class="tw-text-sm tw-mt-2">
                                    {{ $lang->data['vat'] ?? 'VAT' }} : {{ $customer->tax_number }}
                                </div>
                            @endif
                        </div>

                        {{-- Financial summary --}}
                        <div class="tw-flex tw-flex-col">
                            <div class="pb-2">{{ $lang->data['payment_details'] ?? 'Payment Details' }}</div>
                            <div class="tw-flex tw-justify-between tw-items-center tw-w-[17rem] tw-mt-2">
                                <div class="tw-text-sm">{{ $lang->data['total_items'] ?? 'Total Items' }}</div>
                                <div class="tw-text-sm">{{ collect($details)->sum('quantity') }}</div>
                            </div>
                            <div class="tw-flex tw-justify-between tw-items-center tw-w-[17rem] tw-mt-2">
                                <div class="tw-text-sm">{{ $lang->data['sub_total'] ?? 'Sub Total' }}</div>
                                <div class="tw-text-sm">{{ getFormattedCurrency($payload['sub_total'] ?? 0) }}</div>
                            </div>
                            <div class="tw-flex tw-justify-between tw-items-center tw-w-[17rem]">
                                <div class="tw-text-sm">{{ $lang->data['addon'] ?? 'Addon' }}</div>
                                <div class="tw-text-sm">{{ getFormattedCurrency($payload['addon_total'] ?? 0) }}</div>
                            </div>
                            <div class="tw-flex tw-justify-between tw-items-center tw-w-[17rem]">
                                <div class="tw-text-sm">{{ $lang->data['discount'] ?? 'Discount' }}</div>
                                <div class="tw-text-sm">{{ getFormattedCurrency($payload['discount'] ?? 0) }}</div>
                            </div>
                            <div class="tw-flex tw-justify-between tw-items-center tw-w-[17rem]">
                                <div class="tw-text-sm">
                                    {{ $lang->data['tax'] ?? 'Tax' }}
                                    ({{ $payload['tax_percentage'] ?? 0 }}%)
                                </div>
                                <div class="tw-text-sm">{{ getFormattedCurrency($payload['tax_amount'] ?? 0) }}</div>
                            </div>
                            <div class="tw-flex tw-justify-between tw-items-center tw-w-[17rem] tw-mt-2">
                                <div class="tw-font-bold tw-text-sm">{{ $lang->data['gross_total'] ?? 'Gross Total' }}</div>
                                <div class="tw-font-bold tw-text-sm">{{ getFormattedCurrency($payload['total'] ?? 0) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if(!empty($payload['note']))
                        <hr class="tw-mt-4">
                        <div class="tw-flex tw-justify-between tw-text-sm tw-mt-4">
                            <div><span class="tw-font-medium">{{ $lang->data['notes'] ?? 'Notes' }} :</span> {{ $payload['note'] }}</div>
                        </div>
                    @endif

                    {{-- Powered by footer --}}
                    <div class="tw-flex tw-items-center tw-justify-center tw-gap-2 tw-mt-4">
                        <div class="tw-w-full tw-h-[1px] tw-from-transparent tw-to-neutral-300 tw-bg-gradient-to-r"></div>
                        <div class="tw-shrink-0 tw-font-light">{{ $lang->data['powered_by'] ?? 'Powered By' }}<span class="tw-font-bold">{{ getApplicationName() }}</span></div>
                        <div class="tw-w-full tw-h-[1px] tw-from-transparent tw-to-neutral-300 tw-bg-gradient-to-l"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="card h-100 p-0 radius-12 lg:tw-w-[24rem] tw-w-full tw-shrink-0">
            <div class="card-body p-24">
                {{-- Addons --}}
                @if (count($addons) > 0)
                    <div class="tw-text-xl tw-font-medium">{{ $lang->data['service_addons'] ?? 'Service Addons' }}</div>
                    @foreach ($addons as $addon)
                        <div class="tw-flex tw-flex-col bg-gradient-success card tw-mt-2">
                            <div class="card-body">
                                <div class="tw-flex tw-items-center tw-text-sm tw-gap-4">
                                    <div class="tw-relative tw-items-center tw-flex tw-flex-col">
                                        <iconify-icon icon="tabler:puzzle" class="menu-icon tw-text-xl"></iconify-icon>
                                    </div>
                                    <div class="tw-flex tw-flex-col">
                                        <div class="tw-font-medium">{{ $addon['name'] }}</div>
                                        <div>{{ getFormattedCurrency($addon['price']) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Payments --}}
                @if (count($payments) > 0)
                    <div class="tw-text-xl tw-font-medium tw-pt-6">{{ $lang->data['payments'] ?? 'Payments' }}</div>
                    @foreach ($payments as $payment)
                        <div class="tw-flex tw-items-center tw-pt-2 tw-text-sm tw-gap-4">
                            <div class="tw-relative tw-items-center tw-flex tw-flex-col tw-translate-y-1">
                                <iconify-icon icon="tabler:target" class="menu-icon"></iconify-icon>
                                <div class="tw-top-[100%] tw-left-[6px] tw-h-6 tw-w-[2px] tw-bg-neutral-300"></div>
                            </div>
                            <div class="tw-flex tw-flex-col">
                                <div class="tw-font-medium">{{ getFormattedCurrency($payment['amount']) }}</div>
                                <div class="tw-text-xs tw-font-light tw-mt-1">
                                    <span class="tw-font-bold">[{{ getpaymentMode($payment['type']) }}]</span>
                                    @if($payment['notes'])
                                        — {{ $payment['notes'] }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- Request metadata --}}
                <div class="tw-mt-6 tw-pt-6 tw-border-t tw-border-neutral-200">
                    <div class="tw-text-sm tw-font-medium tw-mb-2">{{ $lang->data['request_details'] ?? 'Request Details' }}</div>
                    <div class="tw-text-xs tw-text-neutral-600 tw-space-y-1">
                        <div>{{ $lang->data['created_by'] ?? 'Created By' }}: <span class="tw-font-medium tw-text-neutral-800">{{ $request->user->name ?? 'Unknown' }}</span></div>
                        <div>{{ $lang->data['created_at'] ?? 'Created At' }}: <span class="tw-font-medium tw-text-neutral-800">{{ $request->created_at->format('d/m/Y h:i A') }}</span></div>
                        @if($request->updated_at && $request->updated_at != $request->created_at)
                            <div>{{ $lang->data['updated_at'] ?? 'Updated At' }}: <span class="tw-font-medium tw-text-neutral-800">{{ $request->updated_at->format('d/m/Y h:i A') }}</span></div>
                        @endif
                    </div>
                </div>

                {{-- Action buttons (edit link, if user has permission) --}}
                @if($request->status == 0 || $request->status == 2)
                    @if(Auth::id() == $request->created_by || Auth::user()->hasPermission('edit_pending_requests'))
                        <a href="{{ route('orders.requests.edit', $request->id) }}" class="btn btn-outline-info-600 radius-8 px-20 py-11 tw-mt-6 tw-w-full d-flex align-items-center justify-content-center gap-2">
                            <iconify-icon icon="lucide:edit" class="text-xl"></iconify-icon>
                            {{ $lang->data['edit'] ?? 'Edit Request' }}
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
