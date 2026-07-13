<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h5 class="card-title mb-0">
                {{ $lang->data['recycle_bin'] ?? 'Recycle Bin' }} 
                <span class="text-sm text-secondary-light fw-normal">({{ count($orders) }} orders)</span>
            </h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(count($selectedOrders) > 0)
                    @can('order_restore')
                    <button type="button" class="btn btn-success-600 btn-sm radius-8 d-flex align-items-center gap-1" wire:click="bulkRestore" wire:confirm="Are you sure you want to restore the selected orders?">
                        <iconify-icon icon="mdi:restore"></iconify-icon>
                        {{ $lang->data['restore_selected'] ?? 'Restore Selected' }}
                    </button>
                    @endcan
                    @can('order_force_delete')
                    <button type="button" class="btn btn-danger-600 btn-sm radius-8 d-flex align-items-center gap-1" wire:click="bulkForceDelete" wire:confirm="Are you sure you want to permanently delete the selected orders? This action cannot be undone.">
                        <iconify-icon icon="mdi:delete-forever"></iconify-icon>
                        {{ $lang->data['delete_selected'] ?? 'Delete Selected' }}
                    </button>
                    @endcan
                @endif
                @can('order_force_delete')
                    <button type="button" class="btn btn-outline-danger btn-sm radius-8 d-flex align-items-center gap-1" wire:click="emptyRecycleBin" wire:confirm="Are you sure you want to permanently empty the entire recycle bin? This action cannot be undone.">
                        <iconify-icon icon="mdi:delete-sweep"></iconify-icon>
                        {{ $lang->data['empty_recycle_bin'] ?? 'Empty Recycle Bin' }}
                    </button>
                @endcan
            </div>
        </div>

        <div class="card-body p-0">
            <div class="tw-py-2 tw-px-3 bg-base d-flex align-items-center flex-wrap gap-3 justify-content-between border-bottom">
                <form class="navbar-search w-100 max-w-sm">
                    <input type="text" class="bg-base tw-px-3 tw-py-1.5 w-full radius-8 border" placeholder="{{ $lang->data['search_deleted_orders'] ?? 'Search deleted orders...' }}" wire:model.live.debounce.300ms="search_query">
                    <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                </form>
            </div>

            <div class="alert alert-warning mb-0 radius-0 border-0 border-bottom d-flex align-items-center gap-2 py-2 px-3">
                <iconify-icon icon="lucide:info" class="text-warning-600"></iconify-icon>
                <span class="text-sm text-warning-800">{{ $lang->data['recycle_bin_notice'] ?? 'Deleted orders are automatically purged after 90 days. Restoring an order also restores its details, addons, and payments.' }}</span>
            </div>

            <div class="table-responsive">
                <table class="table bordered-table sm-table mb-0">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col" class="text-center w-40-px">
                                <!-- Could add a select-all checkbox here if desired -->
                            </th>
                            <th scope="col">{{ $lang->data['order_info'] ?? 'Order Info' }}</th>
                            <th scope="col">{{ $lang->data['customer'] ?? 'Customer' }}</th>
                            <th scope="col">{{ $lang->data['order_amount'] ?? 'Order Amount' }}</th>
                            <th scope="col">{{ $lang->data['status'] ?? 'Status' }}</th>
                            <th scope="col">{{ $lang->data['deleted_info'] ?? 'Deleted Info' }}</th>
                            <th scope="col" class="text-center">{{ $lang->data['action'] ?? 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $item)
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input" type="checkbox" wire:model.live="selectedOrders" value="{{ $item->id }}">
                            </td>
                            <td>
                                <div class="tw-flex tw-flex-col">
                                    <div class="text-neutral-600">
                                        {{ $lang->data['order_id'] ?? 'Order ID' }} : <span class="tw-font-medium text-primary-light">{{ $item->order_number }}</span>
                                    </div>
                                    <div class="text-neutral-600">
                                        {{ $lang->data['order_date'] ?? 'Order Date' }} : <span class="tw-font-medium text-primary-light">{{ \Carbon\Carbon::parse($item->order_date)->format('d/m/y') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="mb-0 text-primary-light fw-medium">{{ $item->customer_name ?? ($lang->data['walk_in_customer'] ?? 'Walk In Customer') }}</p>
                                <p class="mb-0 text-secondary-light text-xs">{{$item->phone_number ? getCountryCode() : ''}}{{$item->phone_number ? (int)$item->phone_number : ''}}</p>
                            </td>
                            <td>
                                <div class="tw-flex tw-flex-col">
                                    <div class="text-neutral-600">
                                        {{ $lang->data['total'] ?? 'Total' }} : <span class="tw-font-medium text-primary-light">{{ getFormattedCurrency($item->total) }}</span>
                                    </div>
                                    <div class="text-neutral-600">
                                        {{ $lang->data['paid'] ?? 'Paid' }} : <span class="tw-font-medium text-primary-light">{{ getFormattedCurrency($item->paid_amount) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($item->status == 0)
                                <span class="badge fw-semibold text-neutral-600 bg-neutral-200 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['pending'] ?? 'Pending' }}
                                </span>
                                @elseif($item->status == 1)
                                <span class="badge fw-semibold text-warning-600 bg-warning-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['processing'] ?? 'Processing' }}
                                </span>
                                @elseif($item->status == 2)
                                <span class="badge fw-semibold text-info-600 bg-info-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['ready_to_deliver'] ?? 'Ready To Deliver' }}
                                </span>
                                @elseif($item->status == 3)
                                <span class="badge fw-semibold text-success-600 bg-success-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['delivered'] ?? 'Delivered' }}
                                </span>
                                @elseif($item->status == 4)
                                <span class="badge fw-semibold text-danger-600 bg-danger-100 px-20 py-9 radius-4 text-white">
                                    {{ $lang->data['returned'] ?? 'Returned' }}
                                </span>
                                @endif
                            </td>
                            <td>
                                <div class="tw-flex tw-flex-col">
                                    <div class="text-neutral-600 text-xs mb-1">
                                        {{ $lang->data['deleted_by'] ?? 'Deleted By' }}: <span class="tw-font-medium text-primary-light">{{ $item->deletedBy ? $item->deletedBy->name : 'System' }}</span>
                                    </div>
                                    <div class="text-neutral-600 text-xs">
                                        {{ $lang->data['deleted_on'] ?? 'Deleted On' }}: <span class="tw-font-medium text-primary-light">{{ \Carbon\Carbon::parse($item->deleted_at)->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="mt-1">
                                        @if($item->days_remaining > 30)
                                            <span class="badge bg-success-100 text-success-600 text-xs px-2 py-1 radius-4">{{ $item->days_remaining }} {{ $lang->data['days_left'] ?? 'days left' }}</span>
                                        @elseif($item->days_remaining > 10)
                                            <span class="badge bg-warning-100 text-warning-600 text-xs px-2 py-1 radius-4">{{ $item->days_remaining }} {{ $lang->data['days_left'] ?? 'days left' }}</span>
                                        @else
                                            <span class="badge bg-danger-100 text-danger-600 text-xs px-2 py-1 radius-4">{{ $item->days_remaining }} {{ $lang->data['days_left'] ?? 'days left' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-10 justify-content-center">
                                    @can('order_restore')
                                    <button type="button" wire:click.prevent="restoreOrder({{ $item->id }})" class="bg-success-focus bg-hover-success-200 text-success-600 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" title="Restore Order"> 
                                        <iconify-icon icon="mdi:restore" class="menu-icon"></iconify-icon>
                                    </button>
                                    @endcan
                                    @can('order_force_delete')
                                    <button type="button" wire:click.prevent="forceDeleteOrder({{ $item->id }})" wire:confirm="Are you sure you want to permanently delete this order? This action cannot be undone." class="remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" title="Permanently Delete"> 
                                        <iconify-icon icon="mdi:delete-forever" class="menu-icon"></iconify-icon>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <iconify-icon icon="mdi:recycle" class="text-neutral-300" style="font-size: 4rem;"></iconify-icon>
                                    <h6 class="mt-3 text-neutral-500">{{ $lang->data['recycle_bin_empty'] ?? 'Recycle bin is empty' }}</h6>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
