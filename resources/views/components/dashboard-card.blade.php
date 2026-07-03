@props(['title', 'value', 'color' => 'primary', 'icon' => null, 'trend' => null, 'trendUp' => true])
<div class="card h-100 p-3 radius-12 border-0 shadow-sm tw-bg-white">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <p class="text-sm fw-medium text-muted mb-0">{{ $title }}</p>
        @if($icon)
            <div class="icon-circle bg-{{ $color }}-100 text-{{ $color }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%;">
                <iconify-icon icon="{{ $icon }}"></iconify-icon>
            </div>
        @endif
    </div>
    <h4 class="mb-1 text-{{ $color }} fw-bold">{{ $value }}</h4>
    @if($trend)
        <p class="text-xs mb-0 {{ $trendUp ? 'text-success' : 'text-danger' }}">
            <iconify-icon icon="{{ $trendUp ? 'mdi:trending-up' : 'mdi:trending-down' }}"></iconify-icon>
            {{ $trend }}
        </p>
    @endif
</div>
