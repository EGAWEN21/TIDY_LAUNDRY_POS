<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12">
        <div class="tw-py-1.5 tw-px-3 bg-base d-flex align-items-center flex-wrap gap-3 justify-content-between">
            <div class="tw-flex tw-items-center gap-4">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-1 tw-flex-col">
                        <span class="fw-medium">{{ $lang->data['status'] ?? 'Customer Status' }}</span>
                        <select class="form-select form-select-sm bg-base h-40-px w-auto" wire:model.live="statusFilter">
                            <option class="select-box" value="all">{{$lang->data['all'] ?? 'All Customers'}}</option>
                            <option class="select-box" value="Active">{{$lang->data['active'] ?? 'Active (Last 21 Days)'}}</option>
                            <option class="select-box" value="At-Risk">{{$lang->data['at_risk'] ?? 'At-Risk (> 21 Days)'}}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="tw-flex tw-items-center gap-2">
                <button type="button" onclick="window.print()" class="btn btn-success-100 text-success-600 radius-8 px-16 py-9 tw-flex tw-items-center tw-gap-2">
                    <iconify-icon icon="solar:printer-bold" class="mr-1"></iconify-icon>
                    {{$lang->data['print_report'] ?? 'Print Report'}}
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm tw-min-h-[calc(100vh-16rem)]">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>{{$lang->data['customer'] ?? 'Customer'}}</th>
                            <th>{{$lang->data['registered'] ?? 'Registered'}}</th>
                            <th>{{$lang->data['orders'] ?? 'Orders'}}</th>
                            <th>{{$lang->data['lifetime_spend'] ?? 'Lifetime Spend'}}</th>
                            <th>{{$lang->data['spend_30'] ?? 'Spend (30 Days)'}}</th>
                            <th>{{$lang->data['aov'] ?? 'Avg Order Val'}}</th>
                            <th>{{$lang->data['outstanding'] ?? 'Outstanding'}}</th>
                            <th>{{$lang->data['last_visit'] ?? 'Last Visit'}}</th>
                            <th>{{$lang->data['status'] ?? 'Status'}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customersData as $row)
                        <tr>
                            <td>
                                <p class="text-sm font-weight-bold tw-text-black mb-0">{{$row['name']}}</p>
                                <span class="text-xs text-muted">{{$row['phone']}}</span>
                            </td>
                            <td><p class="text-sm mb-0">{{$row['registration_date']}}</p></td>
                            <td><p class="text-sm mb-0">{{$row['total_orders']}}</p></td>
                            <td><p class="text-sm font-weight-bold text-success mb-0">{{getFormattedCurrency($row['total_spend'])}}</p></td>
                            <td><p class="text-sm mb-0">{{getFormattedCurrency($row['spend_30'])}}</p></td>
                            <td><p class="text-sm mb-0">{{getFormattedCurrency($row['aov'])}}</p></td>
                            <td>
                                @if($row['outstanding'] > 0)
                                    <p class="text-sm font-weight-bold text-danger mb-0">{{getFormattedCurrency($row['outstanding'])}}</p>
                                @else
                                    <p class="text-sm text-muted mb-0">₦0.00</p>
                                @endif
                            </td>
                            <td><p class="text-sm mb-0">{{$row['last_visit']}}</p></td>
                            <td>
                                @if($row['status'] == 'Active')
                                    <span class="badge fw-semibold text-success-600 bg-success-100 px-20 py-9 radius-4 text-white">Active</span>
                                @else
                                    <span class="badge fw-semibold text-danger-600 bg-danger-100 px-20 py-9 radius-4 text-white">At-Risk</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No customers found for this filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
