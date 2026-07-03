@props(['title'])
<div class="card h-100 p-4 radius-12 border-0 shadow-sm tw-bg-white tw-overflow-hidden">
    <h6 class="mb-4 fw-bold tw-text-gray-700">{{ $title }}</h6>
    <div class="chart-wrapper tw-w-full">
        {{ $slot }}
    </div>
</div>
