@php
    $lang = null;
    if (session()->has('selected_language')) {
        $lang = \App\Models\Translation::where('id', session()->get('selected_language'))->first();
    } else {
        $lang = \App\Models\Translation::where('default', 1)->first();
    }
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{$lang->data['account_statement'] ?? 'Account Statement'}}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .business-details { margin-bottom: 20px; }
        .customer-details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f3f4f6; text-transform: uppercase; font-size: 12px; padding: 10px; border: 1px solid #ddd; text-align: center; }
        td { padding: 8px; border: 1px solid #ddd; text-align: center; font-size: 12px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        .bg-light { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $master_settings['store_name'] ?? 'TidyPOS' }}</h2>
        <p>{{ $master_settings['store_address'] ?? '' }}</p>
        <p>{{ $master_settings['store_phone'] ?? '' }} | {{ $master_settings['store_email'] ?? '' }}</p>
        <hr>
        <h3>{{$lang->data['account_statement'] ?? 'Account Statement'}}</h3>
    </div>

    <div style="width: 100%; display: table;">
        <div style="display: table-cell; width: 50%;" class="customer-details">
            <strong>{{$lang->data['customer'] ?? 'Customer'}}:</strong><br>
            {{ $customer->name }}<br>
            {{ $customer->phone }}<br>
            {{ $customer->email ?? '' }}
        </div>
        <div style="display: table-cell; width: 50%; text-align: right;">
            <strong>{{$lang->data['statement_period'] ?? 'Period'}}:</strong><br>
            {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ $lang->data['date'] ?? 'Date' }}</th>
                <th>{{ $lang->data['type'] ?? 'Description' }}</th>
                <th>{{ $lang->data['debit'] ?? 'Debit' }}</th>
                <th>{{ $lang->data['credit'] ?? 'Credit' }}</th>
                <th>{{ $lang->data['balance'] ?? 'Balance' }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $runningBalance = $firstData['debits'] - $firstData['credits'];
            @endphp
            <tr class="bg-light">
                <td colspan="4" class="text-right fw-bold">{{ $lang->data['opening_balance'] ?? 'Opening Balance' }}:</td>
                <td class="fw-bold {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                    {{ getFormattedCurrency(abs($runningBalance)) }} {{ $runningBalance > 0 ? '(Dr)' : '(Cr)' }}
                </td>
            </tr>
            @foreach($transactions as $row)
            @php
                if($row['type'] == 'debit') {
                    $runningBalance += $row['total'];
                } else {
                    $runningBalance -= $row['received_amount'];
                }
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                <td class="text-left">
                    @if($row['type'] == 'debit')
                        Order #{{ $row['order_number'] }}
                    @else
                        Payment
                    @endif
                </td>
                <td class="text-danger">{{ $row['type'] == 'debit' ? getFormattedCurrency($row['total']) : '-' }}</td>
                <td class="text-success">{{ $row['type'] == 'credit' ? getFormattedCurrency($row['received_amount']) : '-' }}</td>
                <td class="fw-bold {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                    {{ getFormattedCurrency(abs($runningBalance)) }} {{ $runningBalance > 0 ? '(Dr)' : '(Cr)' }}
                </td>
            </tr>
            @endforeach
            <tr class="bg-light">
                <td colspan="4" class="text-right fw-bold">{{ $lang->data['closing_balance'] ?? 'Closing Balance' }}:</td>
                <td class="fw-bold {{ $runningBalance > 0 ? 'text-danger' : 'text-success' }}">
                    {{ getFormattedCurrency(abs($runningBalance)) }} {{ $runningBalance > 0 ? '(Dr)' : '(Cr)' }}
                </td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: right;">
        <h4>{{ $lang->data['amount_due'] ?? 'Amount Due' }}: <span class="text-danger">{{ $runningBalance > 0 ? getFormattedCurrency($runningBalance) : getFormattedCurrency(0) }}</span></h4>
    </div>
</body>
</html>
