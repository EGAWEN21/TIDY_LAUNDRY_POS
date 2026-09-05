@php
    $reports = collect();
    /* sales */
    if ($category == 1) {
        $reports = \App\Models\Order::whereDate('order_date', '>=', $from_date)
            ->whereDate('order_date', '<=', $to_date)
            ->where('status', 3)
            ->latest()
            ->get();
    }
    /* expense */
    if ($category == 2) {
        $reports = \App\Models\Expense::whereDate('expense_date', '>=', $from_date)
            ->whereDate('expense_date', '<=', $to_date)
            ->with('expenseCategory')
            ->latest()
            ->get();
    }
    $lang = null;
    if (session()->has('selected_language')) {
        $lang = \App\Models\Translation::where('id', session()->get('selected_language'))->first();
    } else {
        $lang = \App\Models\Translation::where('default', 1)->first() ?? \App\Models\Translation::where('id', 1)->first();
    }
@endphp
<div>
    <!DOCTYPE html
        PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>{{$lang->data['tax_report'] ?? 'Tax Report'}}</title>
        <style>
            #main {
                border-collapse: collapse;
                line-height: 1rem;
                text-align: center;
            }
            th {
                background-color: rgb(101, 104, 101);
                Color: white;
                font-size: 0.75rem;
                line-height: 1rem;
                font-weight: bold;
                text-transform: uppercase;
                text-align: center;
                padding: 10px;
            }
            td {
                text-align: center;
                border: 1px solid;
                font-size: 0.75rem;
                line-height: 1rem;
            }
            .col {
                border: none;
                text-align: left;
                padding: 10px;
                font-size: 0.75rem;
                line-height: 1rem;
            }
        </style>
    </head>
    <body onload="">

        <h3 class="fw-500 text-dark">
            @if ($category == 1)
                {{ 'Sales Tax Report' }}
            @else
                {{ 'Expense Tax Report' }}
            @endif
        </h3>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td class="col"> <label>{{$lang->data['start_date'] ?? 'Start Date'}}: </label>
                    {{ \Carbon\Carbon::parse($from_date)->format('d/m/Y') }}</td>
                <td class="col"> <label>{{$lang->data['end_date'] ?? 'End Date'}}: </label>
                    {{ \Carbon\Carbon::parse($to_date)->format('d/m/Y') }} </td>
            </tr>
        </table>
        <table id="main" width="100%" cellpadding="0" cellspacing="0">
            <thead class="table-dark">
                <tr>
                    <th class="text-uppercase text-secondary text-xs opacity-7">#</th>
                    <th  class="text-uppercase text-secondary text-xs opacity-7">{{$lang->data['date'] ?? 'Date'}}</th>
                    <th  class="text-uppercase text-secondary text-xs opacity-7">{{$lang->data['particulars'] ?? 'Particulars'}} #</th>
                    <th  class="text-uppercase text-secondary text-xs opacity-7">{{$lang->data['before_tax'] ?? 'Before Tax'}}</th>
                    <th  class="text-uppercase text-secondary text-xs opacity-7">{{$lang->data['tax_amount'] ?? 'Tax Amount'}}</th>
                    <th  class="text-uppercase text-secondary text-xs opacity-7">{{$lang->data['total_amount'] ?? 'Total Amount'}}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $tax_amount_total_expense = 0;
                    $tax_amount_total_sales = 0;
                    $total_amount_sales = 0;
                    $total_amount_expense = 0;
                    $i = 1;
                @endphp
                @foreach ($reports as $row)
                    @php
                        if ($category == 1) {
                            $tax_amount = $row->tax_amount;
                            $before_tax = $row->taxable_amount > 0 ? $row->taxable_amount : ($row->total - $row->tax_amount);
                            $row_total = $row->total;
                            $tax_amount_total_sales += $tax_amount;
                            $total_amount_sales += $row_total;
                        } else {
                            $tax_pct = (float) ($row->tax_percentage ?? 0);
                            if ($row->tax_included == 1) {
                                $tax_amount = $tax_pct > 0 ? ($row->expense_amount - ($row->expense_amount / (1 + ($tax_pct / 100)))) : 0;
                                $before_tax = $row->expense_amount - $tax_amount;
                                $row_total = $row->expense_amount;
                            } else {
                                $tax_amount = $row->expense_amount * ($tax_pct / 100);
                                $before_tax = $row->expense_amount;
                                $row_total = $before_tax + $tax_amount;
                            }
                            $tax_amount_total_expense += $tax_amount;
                            $total_amount_expense += $row_total;
                        }
                    @endphp
                    <tr>
                        <td>
                            <p class="text-xs px-3 mb-0">
                                {{ $i++ }}
                            </p>
                        </td>
                        <td>
                            <p class="text-xs px-3 mb-0">
                                @if ($category == 1)
                                    {{ \Carbon\Carbon::parse($row->order_date)->format('d/m/Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($row->expense_date)->format('d/m/Y') }}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="text-xs px-3 mb-0">
                                <span class="font-weight-bold">
                                    @if ($category == 1)
                                        {{ $row->order_number }}
                                    @else
                                        {{ $row->expenseCategory->expense_category_name ?? '' }}
                                    @endif
                                </span>
                            </p>
                        </td>
                        <td>
                            <p class="text-xs px-3 font-weight-bold mb-0">
                                {{ getFormattedCurrency($before_tax) }}
                            </p>
                        </td>
                        <td>
                            <p class="text-xs px-3 font-weight-bold mb-0">
                                {{ getFormattedCurrency($tax_amount) }}
                            </p>
                        </td>
                        <td>
                            <p class="text-xs px-3 font-weight-bold mb-0">
                                {{ getFormattedCurrency($row_total) }}
                            </p>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table cellspacing="15">
            <tr>
                <td class="col">
                    <span class="text-sm mb-0 fw-500">{{$lang->data['total_amount'] ?? 'Total Amount'}}:</span>
                    <span class="text-sm text-dark ms-2 fw-600 mb-0">
                        @if ($category == 1)
                            {{getFormattedCurrency($total_amount_sales)}}
                        @else
                            {{getFormattedCurrency($total_amount_expense)}}
                        @endif
                    </span>
                </td>
                <td class="col"> <span class="text-sm mb-0 fw-500"></span>
                    <span class="text-sm mb-0 fw-500">{{$lang->data['total_tax_amount'] ?? 'Total Tax Amount'}}:</span>
                    <span class="text-sm text-dark ms-2 fw-600 mb-0">
                        @if ($category == 1)
                            {{getFormattedCurrency($tax_amount_total_sales)}}
                        @else
                            {{getFormattedCurrency($tax_amount_total_expense)}}
                        @endif
                    </span>
                </td>
            </tr>
        </table>
    </body>
    </html>
</div>