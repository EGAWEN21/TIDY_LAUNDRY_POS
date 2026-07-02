<div class="dropdown">
    <button class="d-flex justify-content-center align-items-center rounded-circle position-relative border-0 bg-transparent" type="button" data-bs-toggle="dropdown">
        <div class="tw-w-8 tw-h-8 sm:tw-w-10 sm:tw-h-10 tw-flex tw-items-center tw-justify-center btn-primary-600 text-white tw-rounded-full position-relative">
            <iconify-icon icon="heroicons:bell-solid" class="icon text-xl"></iconify-icon>
            @if($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; transform: translate(-30%, -30%) !important;">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>
    </button>
    <div class="dropdown-menu to-top dropdown-menu-sm" style="width: 320px; right: 0; left: auto;">
        <div class="py-12 px-16 radius-8 bg-primary-50 d-flex align-items-center justify-content-between gap-2 mb-2">
            <h6 class="text-md text-primary-light fw-semibold mb-0">Notifications</h6>
            @if($unreadCount > 0)
                <button type="button" class="text-primary-600 text-xs fw-semibold hover-text-primary border-0 bg-transparent" wire:click.prevent="markAllAsRead">Mark all read</button>
            @endif
        </div>
        <ul class="to-top-list p-0 m-0" style="max-height: 300px; overflow-y: auto;">
            @forelse($notifications as $notification)
                <li class="px-16 py-12 border-bottom hover-bg-neutral-50 cursor-pointer d-flex justify-content-between align-items-start" wire:click.prevent="markAsRead('{{ $notification->id }}')">
                    <div>
                        <span class="text-xs fw-semibold text-{{ $notification->data['type'] == 'success' ? 'success' : ($notification->data['type'] == 'warning' ? 'warning' : 'primary') }}">
                            {{ $notification->data['title'] ?? 'Alert' }}
                        </span>
                        <p class="text-sm text-secondary-light mb-1 mt-1 line-height-1-4">
                            {{ $notification->data['message'] ?? '' }}
                        </p>
                        <span class="text-xs text-neutral-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                </li>
            @empty
                <li class="px-16 py-24 text-center">
                    <p class="text-sm text-secondary-light mb-0">No new notifications.</p>
                </li>
            @endforelse
        </ul>
        <div class="px-16 py-12 text-center border-top">
            <a href="{{ route('notifications.index') }}" class="text-primary-600 text-sm fw-semibold hover-text-primary">View All Notifications</a>
        </div>
    </div>
</div>
