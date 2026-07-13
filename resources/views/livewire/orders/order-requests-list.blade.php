<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12">
        <div class="tw-py-1.5 tw-px-3 bg-base d-flex align-items-center flex-wrap gap-3 justify-content-between">
            <h5 class="mb-0">{{ $lang->data['order_requests'] ?? 'Order Requests' }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">{{ $lang->data['request_number'] ?? 'Request Number' }}</th>
                            <th scope="col">{{ $lang->data['created_by'] ?? 'Created By' }}</th>
                            <th scope="col">{{ $lang->data['customer_name'] ?? 'Customer' }}</th>
                            <th scope="col">{{ $lang->data['total'] ?? 'Total' }}</th>
                            <th scope="col">{{ $lang->data['status'] ?? 'Status' }}</th>
                            <th scope="col" class="text-center">{{ $lang->data['action'] ?? 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($requests) > 0)
                            @foreach ($requests as $item)
                                <tr>
                                    <td>{{ $item->request_number }}</td>
                                    <td>{{ $item->user->name ?? 'Unknown' }}</td>
                                    <td>{{ $item->customer_name ?? 'Walk-in Customer' }}</td>
                                    <td>{{ getFormattedCurrency($item->total_amount) }}</td>
                                    <td>
                                        @if($item->status == 0)
                                            <span class="badge bg-warning-600 text-warning-600 bg-opacity-20">{{ $lang->data['pending_approval'] ?? 'Pending Approval' }}</span>
                                        @elseif($item->status == 1)
                                            <span class="badge bg-danger-600 text-danger-600 bg-opacity-20">{{ $lang->data['rejected'] ?? 'Rejected' }}</span>
                                            @if($item->rejection_note)
                                                <div class="text-xs text-danger mt-1">{{ $item->rejection_note }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            @can('accept_reject_order')
                                                @if($item->status == 0)
                                                    <button type="button" class="btn btn-success text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" wire:click="acceptOrder({{ $item->id }})">
                                                        <iconify-icon icon="lucide:check-circle" class="icon text-xl line-height-1"></iconify-icon>
                                                        {{ $lang->data['accept'] ?? 'Accept' }}
                                                    </button>
                                                    <button type="button" class="btn btn-danger text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" wire:click="rejectModal({{ $item->id }})" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                                        <iconify-icon icon="lucide:x-circle" class="icon text-xl line-height-1"></iconify-icon>
                                                        {{ $lang->data['reject'] ?? 'Reject' }}
                                                    </button>
                                                @endif
                                            @endcan
                                            @if(Auth::id() == $item->created_by || Auth::user()->hasPermission('edit_pending_requests'))
                                                <a href="{{ route('orders.requests.edit', $item->id) }}" class="bg-info-100 text-info-600 bg-hover-info-200 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle">
                                                    <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                                </a>
                                            @endif
                                            @if(Auth::id() == $item->created_by || Auth::user()->hasPermission('delete_order_requests') || Auth::user()->hasPermission('accept_reject_order'))
                                                <button type="button" class="bg-danger-100 text-danger-600 bg-hover-danger-200 fw-medium tw-size-8 d-flex justify-content-center align-items-center rounded-circle" onclick="confirmDelete(() => @this.deleteRequest({{ $item->id }}))">
                                                    <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center">No order requests found...</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md">{{ $lang->data['reject_order'] ?? 'Reject Order Request' }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <form wire:submit.prevent="rejectOrder">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">{{ $lang->data['rejection_note'] ?? 'Rejection Note' }}</label>
                            <textarea class="form-control radius-8" wire:model="rejection_note" rows="3" required></textarea>
                            @error('rejection_note') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-3 mt-24">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click.prevent="$dispatch('closemodal')">{{ $lang->data['cancel'] ?? 'Cancel' }}</button>
                            <button type="submit" class="btn btn-danger">{{ $lang->data['reject'] ?? 'Reject' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
