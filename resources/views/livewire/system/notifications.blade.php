<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">System Notifications</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Notifications</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">All Notifications</h5>
            <div class="d-flex gap-3">
                <button type="button" class="btn btn-outline-primary-600" wire:click="markAllAsRead">Mark All as Read</button>
                <button type="button" class="btn btn-danger" wire:click="deleteAll" wire:confirm="Are you sure you want to delete all notifications?">Clear All</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Status</th>
                            <th scope="col">Type</th>
                            <th scope="col">Message</th>
                            <th scope="col">Date & Time</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                        <tr class="{{ is_null($notification->read_at) ? 'bg-primary-50' : '' }}">
                            <td>
                                @if(is_null($notification->read_at))
                                    <span class="badge bg-danger-focus text-danger-main px-10 py-4 radius-8">Unread</span>
                                @else
                                    <span class="badge bg-success-focus text-success-main px-10 py-4 radius-8">Read</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-{{ isset($notification->data['type']) && $notification->data['type'] == 'success' ? 'success' : (isset($notification->data['type']) && $notification->data['type'] == 'warning' ? 'warning' : 'primary') }}">
                                    {{ $notification->data['title'] ?? 'System' }}
                                </span>
                            </td>
                            <td>{{ $notification->data['message'] ?? '' }}</td>
                            <td>{{ $notification->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if(is_null($notification->read_at))
                                    <button class="btn btn-sm btn-outline-primary" wire:click="markAsRead('{{ $notification->id }}')">Mark Read</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No notifications found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</div>
