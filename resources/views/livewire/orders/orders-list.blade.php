<div class="dashboard-main-body">
    <div class="card h-100 p-0">
        {{-- Search + Add Order Row --}}
        <div class="tw-py-1.5 tw-px-3 bg-base d-flex align-items-center flex-wrap gap-3 justify-content-between">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <form class="navbar-search">
                    <input type="text" class="bg-base tw-px-3 tw-py-1.5 w-auto" name="search" placeholder="{{ $lang->data['search_here'] ?? 'Search Here' }}" wire:model.live="search_query">
                    <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                </form>
            </div>
            @can('order_create')
            <a href="{{route('orders.pos')}}" type="button" class="btn btn-primary text-sm btn-sm radius-8 d-flex align-items-center gap-2" >
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                {{ $lang->data['add_new_order'] ?? 'Add New Order' }}
            </a>
            @endcan
        </div>

        {{-- Quick Period Filter Pills --}}
        <div class="tw-py-2 tw-px-3 bg-base d-flex align-items-center flex-wrap justify-content-between" style="border-top: 1px solid #e5e7eb;">
            <div class="order-period-pills">
                <button type="button" wire:click="applyDatePreset('today')" class="order-period-pill {{ $date_preset === 'today' ? 'active' : '' }}">
                    <iconify-icon icon="lucide:calendar-check"></iconify-icon>
                    {{ $lang->data['today'] ?? 'Today' }}
                </button>
                <button type="button" wire:click="applyDatePreset('yesterday')" class="order-period-pill {{ $date_preset === 'yesterday' ? 'active' : '' }}">
                    <iconify-icon icon="lucide:calendar-minus"></iconify-icon>
                    {{ $lang->data['yesterday'] ?? 'Yesterday' }}
                </button>
                <button type="button" wire:click="applyDatePreset('this_week')" class="order-period-pill {{ $date_preset === 'this_week' ? 'active' : '' }}">
                    <iconify-icon icon="lucide:calendar-range"></iconify-icon>
                    {{ $lang->data['this_week'] ?? 'This Week' }}
                </button>
                <button type="button" wire:click="applyDatePreset('this_month')" class="order-period-pill {{ $date_preset === 'this_month' ? 'active' : '' }}">
                    <iconify-icon icon="lucide:calendar-days"></iconify-icon>
                    {{ $lang->data['this_month'] ?? 'This Month' }}
                </button>
                <button type="button" 
                    @click="$wire.set('date_preset', $wire.date_preset === 'custom' ? null : 'custom')"
                    class="order-period-pill {{ $date_preset === 'custom' ? 'active' : '' }}">
                    <iconify-icon icon="lucide:calendar-search"></iconify-icon>
                    {{ $lang->data['custom'] ?? 'Custom' }}
                </button>
            </div>
            @if($date_from && $date_to && $date_preset)
            <button type="button" wire:click="clearDateFilter" class="order-period-pill" style="border-color: #dc3545; color: #dc3545;">
                <iconify-icon icon="lucide:x"></iconify-icon>
                {{ $lang->data['clear_filter'] ?? 'Clear' }}
            </button>
            @endif
        </div>

        {{-- Custom Date Range Picker (shown when Custom is active) --}}
        @if($date_preset === 'custom')
        <div class="order-date-range-bar" x-data="{
            fromDate: @entangle('date_from').live,
            toDate: @entangle('date_to').live,
            fpFrom: null,
            fpTo: null,
            init() {
                this.fpFrom = flatpickr(this.$refs.dateFrom, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'M j, Y',
                    defaultDate: this.fromDate,
                    onChange: (selectedDates, dateStr) => { this.fromDate = dateStr; }
                });
                this.fpTo = flatpickr(this.$refs.dateTo, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'M j, Y',
                    defaultDate: this.toDate,
                    onChange: (selectedDates, dateStr) => { this.toDate = dateStr; }
                });
            }
        }" wire:ignore.self>
            <iconify-icon icon="lucide:calendar" style="font-size: 1.1rem; color: #487fff;"></iconify-icon>
            <input type="text" x-ref="dateFrom" class="form-control" placeholder="{{ $lang->data['from_date'] ?? 'From date' }}">
            <span class="range-separator">→</span>
            <input type="text" x-ref="dateTo" class="form-control" placeholder="{{ $lang->data['to_date'] ?? 'To date' }}">
            <button type="button" class="btn btn-primary btn-sm radius-8" @click="$wire.applyDateRange(fromDate, toDate)">
                <iconify-icon icon="lucide:search" class="me-1"></iconify-icon>
                {{ $lang->data['filter'] ?? 'Filter' }}
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm radius-8" wire:click="clearDateFilter">
                {{ $lang->data['clear'] ?? 'Clear' }}
            </button>
        </div>
        @endif

        {{-- Orders Table with Date Grouping --}}
        <div class="tw-p-0">
            <div class="table-responsive scroll-sm" style="max-height: 75vh; overflow-y: auto;">
                <table class="table bordered-table sm-table mb-0">
                  <thead style="position: sticky; top: 0; z-index: 10; background: #fff;">
                    <tr>
                      <th scope="col" class="tw-w-10"></th>
                      <th scope="col" class="">{{ $lang->data['order_info'] ?? 'Order Info' }}</th>
                      <th scope="col" class="">{{ $lang->data['customer'] ?? 'Customer' }}</th>
                      <th scope="col" class="">{{ $lang->data['order_amount'] ?? 'Order Amount' }}</th>
                      <th scope="col" class=""> {{ $lang->data['status'] ?? 'Status' }}</th>
                      <th scope="col" class="">{{ $lang->data['payment'] ?? 'Payment' }}</th>
                      <th scope="col" class=""> {{ $lang->data['created_by'] ?? 'Created By' }}</th>
                      <th scope="col" class="text-center">{{ $lang->data['action'] ?? 'Action' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php
                        $groupedOrders = $this->getGroupedOrders();
                        $today = \Carbon\Carbon::today();
                        $yesterday = \Carbon\Carbon::yesterday();
                    @endphp
                    @forelse ($groupedOrders as $dateKey => $group)
                        @php
                            $carbonDate = \Carbon\Carbon::parse($dateKey);
                            if ($carbonDate->isToday()) {
                                $dateLabel = ($lang->data['today'] ?? 'Today');
                                $dateSublabel = $carbonDate->format('l, M j');
                            } elseif ($carbonDate->isYesterday()) {
                                $dateLabel = ($lang->data['yesterday'] ?? 'Yesterday');
                                $dateSublabel = $carbonDate->format('l, M j');
                            } elseif ($carbonDate->isCurrentWeek()) {
                                $dateLabel = $carbonDate->format('l');
                                $dateSublabel = $carbonDate->format('M j, Y');
                            } else {
                                $dateLabel = $carbonDate->format('l, M j');
                                $dateSublabel = $carbonDate->format('Y');
                            }
                            $isCollapsed = in_array($dateKey, $collapsedGroups);
                        @endphp
                        {{-- Date Group Header --}}
                        <tr class="order-date-header" wire:click="toggleGroup('{{ $dateKey }}')">
                            <td colspan="8">
                                <div class="order-date-header__content">
                                    <div class="order-date-header__left">
                                        <div class="tw-mr-2" wire:click.stop>
                                            @php
                                                $orderIds = collect($group['orders'])->pluck('id')->map(fn($id) => (string)$id)->toArray();
                                                $allSelected = empty(array_diff($orderIds, $selectedOrders));
                                            @endphp
                                            <input type="checkbox" class="form-check-input tw-w-4 tw-h-4" 
                                                {{ $allSelected ? 'checked' : '' }}
                                                wire:click="toggleDateGroup('{{ $dateKey }}', {{ json_encode($orderIds) }})">
                                        </div>
                                        <div class="order-date-header__icon">
                                            <iconify-icon icon="lucide:calendar"></iconify-icon>
                                        </div>
                                        <div>
                                            <div class="order-date-header__label">{{ $dateLabel }}</div>
                                            <div class="order-date-header__sublabel">{{ $dateSublabel }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="order-date-header__stats">
                                            <span class="order-date-header__stat">
                                                <iconify-icon icon="lucide:package"></iconify-icon>
                                                <strong>{{ $group['count'] }}</strong> {{ $group['count'] === 1 ? ($lang->data['order'] ?? 'order') : ($lang->data['orders'] ?? 'orders') }}
                                            </span>
                                            <span class="order-date-header__stat">
                                                <iconify-icon icon="lucide:trending-up"></iconify-icon>
                                                {{ $lang->data['sales'] ?? 'Sales' }}: <strong>{{ getFormattedCurrency($group['total_sales']) }}</strong>
                                            </span>
                                            <span class="order-date-header__stat">
                                                <iconify-icon icon="lucide:wallet"></iconify-icon>
                                                {{ $lang->data['collected'] ?? 'Collected' }}: <strong>{{ getFormattedCurrency($group['total_paid']) }}</strong>
                                            </span>
                                        </div>
                                        <iconify-icon icon="lucide:chevron-down" class="order-date-header__toggle {{ $isCollapsed ? 'collapsed' : '' }}"></iconify-icon>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        {{-- Order Rows for this Date Group --}}
                        @if(!$isCollapsed)
                        @foreach ($group['orders'] as $item)
                        <tr class="tw-text-xs order-row-status-{{ $item->status }}">
                            <td class="tw-w-10 tw-align-middle">
                                <input type="checkbox" class="form-check-input tw-w-4 tw-h-4" value="{{ $item->id }}" wire:model.live="selectedOrders">
                            </td>
                            <td>
                                <div class="tw-flex tw-flex-col">
                                    <div class="text-neutral-600">
                                        {{ $lang->data['order_id'] ?? 'Order ID' }} : <span class="tw-font-medium text-primary-light">{{ $item->order_number }}</span> 
                                    </div>
                                    <div class="text-neutral-600">
                                        {{ $lang->data['order_date'] ?? 'Order Date' }} : <span class="tw-font-medium text-primary-light">{{ \Carbon\Carbon::parse($item->order_date)->format('d/m/y') }}</span> 
                                    </div>
                                    <div class="text-neutral-600">
                                        {{ $lang->data['delivery_date'] ?? 'Delivery Date' }} : <span class="tw-font-medium text-primary-light">{{ \Carbon\Carbon::parse($item->delivery_date)->format('d/m/y') }}</span> 
                                    </div>
                                    {{-- Relative Time --}}
                                    @php
                                        $orderTime = \Carbon\Carbon::parse($item->order_date);
                                    @endphp
                                    @if($orderTime->isToday())
                                    <div class="order-relative-time">
                                        <iconify-icon icon="lucide:clock"></iconify-icon>
                                        {{ $orderTime->diffForHumans() }}
                                    </div>
                                    @elseif($orderTime->isYesterday())
                                    <div class="order-relative-time">
                                        <iconify-icon icon="lucide:clock"></iconify-icon>
                                        {{ ($lang->data['yesterday'] ?? 'Yesterday') }} {{ $orderTime->format('g:i A') }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="">
                                <p>{{ $item->customer_name ?? ($lang->data['walk_in_customer'] ?? 'Walk In Customer') }}</p>
                                <p>{{$item->phone_number ? getCountryCode() : ''}}{{$item->phone_number ? (int)$item->phone_number : ''}}</p>
                            </td>
                            <td class="text-primary">
                                {{ getFormattedCurrency($item->total) }}
                            </td>
                            <td class="">
                                @if ($item->status == 0)
                                <span class="badge  fw-semibold text-neutral-600 bg-neutral-200 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['pending'] ?? 'Pending' }}
                                </span>
                                @elseif($item->status == 1)
                                <span class="badge  fw-semibold text-warning-600 bg-warning-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['processing'] ?? 'Processing' }}
                                </span>
                                @elseif($item->status ==2)
                                <span class="badge  fw-semibold text-info-600 bg-info-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['ready_to_deliver'] ?? 'Ready To Deliver' }}
                                </span>
                                @elseif($item->status == 3)
                                <span class="badge  fw-semibold text-success-600 bg-success-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['delivered'] ?? 'Delivered' }}
                                </span>
                                @elseif($item->status == 4)
                                <span class="badge  fw-semibold text-danger-600 bg-danger-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['returned'] ?? 'Returned' }}
                                </span>
                                @endif
                            </td>
                            <td>
                                @php
                                $paidamount = \App\Models\Payment::where('order_id', $item->id)->sum('received_amount');
                                @endphp
                                <div class="tw-flex tw-flex-col">
                                    <div class="text-neutral-600">
                                        {{ $lang->data['total_amount'] ?? 'Total Amount' }} : <span class="tw-font-medium text-primary-light">{{ getFormattedCurrency($item->total) }}</span> 
                                    </div>
                                    <div class="text-neutral-600">
                                        @php
                                        $current_paid_amount = \App\Models\Payment::where('order_id',$item->id)->sum('received_amount');
                                        @endphp
                                        {{ $lang->data['paid_amount'] ?? 'Paid Amount' }} : <span class="tw-font-medium text-primary-light"> {{ getFormattedCurrency($current_paid_amount) }}</span> 
                                    </div>
                                    @if ($paidamount < $item->total)
                                        @if($item->status != 4)
                                        @can('payment_create')
                                            <div class="tw-mt-1">
                                                <button type="button" class="btn rounded-pill btn-success-100 text-success-600 radius-8 tw-text-xs tw-py-1 tw-px-2 " data-bs-toggle="modal" data-bs-target="#exampleModal" wire:click="payment({{ $item->id }})">{{ $lang->data['add_payment'] ?? 'Add Payment' }}</button>
                                            </div>
                                        @endcan
                                        @endif
                                    @else
                                    @if($item->status != 4)
                                    <div class="tw-mt-1">
                                        <button type="button" class="btn rounded-pill btn-neutral-300 text-neutral-600 radius-8 tw-text-xs tw-py-1 tw-px-2 " >{{ $lang->data['fully_paid'] ?? 'Fully Paid' }}</button>
                                    </div>
                                    @endif
                                    @endif

                                </div>
                            </td>
                            <td class="">
                                {{ $item->user->name ?? "" }}
                            </td>
                            <td class="text-center"> 
                                <div class="d-flex align-items-center gap-10 justify-content-center">
                                    @if(Auth::user()->can('order_view') || Auth::user()->can('order_print'))
                                    <div class="dropdown">
                                        <button type="button" class="bg-success-100 text-success-600 bg-hover-success-200 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" data-bs-toggle="dropdown" aria-expanded="false" title="Document Actions">
                                            <iconify-icon icon="lucide:file-text" class="menu-icon"></iconify-icon>
                                        </button>
                                        <ul class="dropdown-menu">
                                            @can('order_view')
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{route('order.view',$item->id)}}"><iconify-icon icon="lucide:eye"></iconify-icon> {{ $lang->data['view_order'] ?? 'View Order' }}</a></li>
                                            @endcan
                                            @can('order_print')
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{route('order.print',$item->id)}}" target="_blank"><iconify-icon icon="lucide:printer"></iconify-icon> {{ $lang->data['print_pdf'] ?? 'Print as PDF' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{route('order.print',$item->id)}}?download_image=1" target="_blank"><iconify-icon icon="lucide:image"></iconify-icon> {{ $lang->data['download_image'] ?? 'Download as Image' }}</a></li>
                                            @endcan
                                        </ul>
                                    </div>
                                    @endif

                                    @can('order_status_change')
                                    <div class="dropdown">
                                        <button type="button" class="bg-warning-100 text-warning-600 bg-hover-warning-200 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                            <iconify-icon icon="lucide:list-checks" class="menu-icon"></iconify-icon>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="changeOrderStatus({{ $item->id }}, 0)"><iconify-icon icon="lucide:clock"></iconify-icon> {{ $lang->data['pending'] ?? 'Pending' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="changeOrderStatus({{ $item->id }}, 1)"><iconify-icon icon="lucide:loader"></iconify-icon> {{ $lang->data['processing'] ?? 'Processing' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="changeOrderStatus({{ $item->id }}, 2)"><iconify-icon icon="lucide:check-circle"></iconify-icon> {{ $lang->data['ready_to_deliver'] ?? 'Ready To Deliver' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="changeOrderStatus({{ $item->id }}, 3)"><iconify-icon icon="lucide:truck"></iconify-icon> {{ $lang->data['delivered'] ?? 'Delivered' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="changeOrderStatus({{ $item->id }}, 4)"><iconify-icon icon="lucide:rotate-ccw"></iconify-icon> {{ $lang->data['returned'] ?? 'Returned' }}</a></li>
                                        </ul>
                                    </div>
                                    @endcan
                                    @can('order_edit')
                                    <a href="{{route('orders.pos.edit',$item->id)}}" class="bg-info-100 text-info-600 bg-hover-info-200 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" >
                                        <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                    </a>
                                    @endcan
                                    @can('order_delete')
                                    <button type="button" wire:click.prevent="deleteOrder({{$item->id}})" class="remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle"> 
                                        <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                    </button>
                                    @endcan
                                    
                                    <div class="dropdown">
                                        <button type="button" class="bg-primary-100 text-primary-600 bg-hover-primary-200 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" data-bs-toggle="dropdown" aria-expanded="false" title="Send Receipt">
                                            <iconify-icon icon="lucide:send" class="menu-icon"></iconify-icon>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="sendReceiptEmail({{ $item->id }})"><iconify-icon icon="lucide:mail"></iconify-icon> {{ $lang->data['send_email'] ?? 'Send Email' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="sendReceiptSMS({{ $item->id }})"><iconify-icon icon="lucide:message-square"></iconify-icon> {{ $lang->data['send_sms'] ?? 'Send SMS' }}</a></li>
                                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="sendReceiptWhatsApp({{ $item->id }})"><iconify-icon icon="mdi:whatsapp"></iconify-icon> {{ $lang->data['send_whatsapp'] ?? 'Send WhatsApp' }}</a></li>
                                        </ul>
                                    </div>

                                </div>
                            </td> 
                        </tr>
                        @endforeach
                        @endif
                    @empty
                        {{-- No orders at all --}}
                    @endforelse
                  </tbody>
                </table>
                @if(count($orders) == 0)
                    <x-empty-item/>
                @endif
                @if($hasMorePages)
                <div x-data="{
                        init () {
                            let observer = new IntersectionObserver((entries) => {
                                entries.forEach(entry => {
                                    if (entry.isIntersecting) {
                                        @this.call('loadOrders')
                                        console.log('loading...')
                                    }
                                })
                            }, {
                                root: null
                            });
                            observer.observe(this.$el);
                        }
                    }" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mt-4">
                    <div class="text-center pb-2 d-flex justify-content-center align-items-center">
                    {{ $lang->data['loading'] ?? 'Loading...' }}
                        <div class="spinner-grow d-inline-flex mx-2 text-primary" role="status">
                            <span class="visually-hidden"> {{ $lang->data['loading'] ?? 'Loading...' }}</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-md modal-dialog modal-dialog-centered">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">{{ $lang->data['payment_details'] ?? 'Payment Details' }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @if ($order)
                    <div class="modal-body p-24">
                        <form action="#">
                            <div class="row">   
                                <div class="col-12">
                                    <div class="">
                                        <ul>
                                            <li class="d-flex align-items-center gap-1 mb-12 tw-justify-between tw-w-full">
                                                <span class="text-md fw-semibold text-primary-light">{{ $lang->data['customer'] ?? 'Customer' }} :</span>
                                                <span class="text-secondary-light fw-medium">{{ $customer_name }}</span>
                                            </li>
                                            <li class="d-flex align-items-center gap-1 mb-12 tw-justify-between ">
                                                <span class="text-md fw-semibold text-primary-light"> {{ $lang->data['order_id'] ?? 'Order ID' }} :</span>
                                                <span class="text-secondary-light fw-medium">{{ $order->order_number }}</span>
                                            </li>
                                            <li class="d-flex align-items-center gap-1 mb-12 tw-justify-between">
                                                <span class="text-md fw-semibold text-primary-light">  {{ $lang->data['order_date'] ?? 'Order Date' }} :</span>
                                                <span class="text-secondary-light fw-medium">{{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}</span>
                                            </li>
                                            <li class="d-flex align-items-center gap-1 mb-12 tw-justify-between">
                                                <span class="text-md fw-semibold text-primary-light">  {{ $lang->data['delivery_date'] ?? 'Delivery Date' }} :</span>
                                                <span class="text-secondary-light fw-medium">{{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}</span>
                                            </li>
                                            <li class="d-flex align-items-center gap-1 mb-12 tw-justify-between">
                                                <span class="text-md fw-semibold text-primary-light"> {{ $lang->data['order_amount'] ?? 'Order Amount' }} :</span>
                                                <span class="text-secondary-light fw-medium"> {{ getFormattedCurrency($order->total) }}</span>
                                            </li>
                                            <li class="d-flex align-items-center gap-1 mb-12 tw-justify-between">
                                                <span class="text-md fw-semibold text-primary-light"> {{ $lang->data['paid_amount'] ?? 'Paid Amount' }} :</span>
                                                <span class="text-secondary-light fw-medium"> {{ getFormattedCurrency($paid_amount) }}</span>
                                            </li>
                                            <li class="d-flex align-items-center gap-1 tw-justify-between">
                                                <span class="text-md fw-semibold text-primary-light"> {{ $lang->data['balance'] ?? 'Balance' }} :</span>
                                                <span class="text-secondary-light fw-medium"> {{ getFormattedCurrency($order->total - $paid_amount) }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-12 tw-my-6">
                                    <hr>
                                </div>
                                <div class="col-12 mb-20 ">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">{{ $lang->data['paid_amount'] ?? 'Paid Amount' }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control radius-8" placeholder="{{ $lang->data['enter_amount'] ?? 'Enter Amount' }}" wire:model="balance" >
                                    @error('balance')
                                        <span class="error text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-12 mb-20 ">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">{{ $lang->data['payment_type'] ?? 'Payment Type' }} <span class="text-danger">*</span></label>
                                    <select  class="form-select radius-8" wire:model="payment_mode">
                                        <option value="">
                                            {{ $lang->data['choose_payment_type'] ?? 'Choose Payment Type' }}
                                        </option>
                                        <option class="select-box" value="1">
                                            {{ $lang->data['cash'] ?? 'Cash' }}
                                        </option>
                                        <option class="select-box" value="2">
                                            {{ $lang->data['upi'] ?? 'UPI' }}
                                        </option>
                                        <option class="select-box" value="3">
                                            {{ $lang->data['card'] ?? 'Card' }}
                                        </option>
                                        <option class="select-box" value="4">
                                            {{ $lang->data['cheque'] ?? 'Cheque' }}
                                        </option>
                                        <option class="select-box" value="5">
                                            {{ $lang->data['bank_transfer'] ?? 'Bank Transfer' }}
                                        </option>
                                    </select>
                                    @error('payment_mode')
                                    <span class="error text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-12 mb-20">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">{{ $lang->data['notes'] ?? 'Notes' }} </label>
                                    <textarea class="form-control radius-8" placeholder="{{ $lang->data['enter_notes'] ?? 'Enter Notes' }}"  wire:model="note"></textarea>
                                    @error('note')
                                        <span class="error text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                                    <button data-bs-dismiss="modal" type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8"> 
                                    {{ $lang->data['cancel'] ?? 'Cancel' }}
                                    </button>
                                    <button type="button" wire:click.prevent="addPayment()" class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8"> 
                                    {{ $lang->data['save'] ?? 'Save' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Floating Bulk Action Bar --}}
    @if(count($selectedOrders) > 0)
    <div class="bulk-action-bar">
        <div class="bulk-action-bar__count">
            <div class="bulk-action-bar__badge">
                {{ count($selectedOrders) }}
            </div>
            <span>{{ count($selectedOrders) === 1 ? 'Order' : 'Orders' }} Selected</span>
        </div>
        
        <div class="bulk-action-bar__actions">
            @can('bulk_order_status_change')
            <div class="dropdown">
                <button type="button" class="btn btn-outline-primary btn-sm radius-8 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <iconify-icon icon="lucide:list-checks"></iconify-icon>
                    Change Status
                </button>
                <ul class="dropdown-menu mb-2">
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="bulkChangeStatus(0)"><iconify-icon icon="lucide:clock"></iconify-icon> Pending</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="bulkChangeStatus(1)"><iconify-icon icon="lucide:loader"></iconify-icon> Processing</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="bulkChangeStatus(2)"><iconify-icon icon="lucide:check-circle"></iconify-icon> Ready To Deliver</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="bulkChangeStatus(3)"><iconify-icon icon="lucide:truck"></iconify-icon> Delivered</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" wire:click="bulkChangeStatus(4)"><iconify-icon icon="lucide:rotate-ccw"></iconify-icon> Returned</a></li>
                </ul>
            </div>
            @endcan
            
            @can('bulk_order_delete')
            <button type="button" class="btn btn-danger-100 text-danger-600 btn-sm radius-8 d-flex align-items-center gap-2" wire:click="bulkDelete" wire:confirm="Are you sure you want to delete these {{ count($selectedOrders) }} orders? This cannot be undone.">
                <iconify-icon icon="fluent:delete-24-regular"></iconify-icon>
                Delete
            </button>
            @endcan
            
            <button type="button" class="btn btn-neutral-100 btn-sm radius-8" wire:click="$set('selectedOrders', [])">
                Cancel
            </button>
        </div>
    </div>
    @endif
    
    @script
    <script>
        $wire.on('open-url', (event) => {
            window.open(event[0].url, '_blank');
        });
    </script>
    @endscript
</div>