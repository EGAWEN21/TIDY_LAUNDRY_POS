<!DOCTYPE html>
<html>
<head>
    <title>Your Order Receipt</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="text-align: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
        <h2>{{ getApplicationName() }}</h2>
        <p>Order Receipt</p>
    </div>

    <div style="margin-bottom: 20px;">
        <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</p>
        <p><strong>Expected Delivery:</strong> {{ \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') }}</p>
        <p><strong>Status:</strong> {{ getOrderStatus($order->status, true) }}</p>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Item</th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Qty</th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if($order->details)
                @foreach($order->details as $detail)
                    @php
                        $serviceName = $detail->service ? $detail->service->service_title : 'Item';
                    @endphp
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">{{ $serviceName }} ({{ $detail->service_name }})</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">{{ $detail->service_quantity }}</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">{{ getFormattedCurrency($detail->service_detail_total) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div style="text-align: right; margin-bottom: 30px;">
        <p><strong>Subtotal:</strong> {{ getFormattedCurrency($order->sub_total) }}</p>
        @if($order->addon_total > 0)
        <p><strong>Addons:</strong> {{ getFormattedCurrency($order->addon_total) }}</p>
        @endif
        @if($order->discount > 0)
        <p><strong>Discount:</strong> {{ getFormattedCurrency($order->discount) }}</p>
        @endif
        <p><strong>Tax:</strong> {{ getFormattedCurrency($order->tax_amount) }}</p>
        <h3 style="color: #2c3e50;">Gross Total: {{ getFormattedCurrency($order->total) }}</h3>
        
        @php
            $paid = \App\Models\Payment::where('order_id', $order->id)->sum('received_amount');
        @endphp
        <p><strong>Amount Paid:</strong> {{ getFormattedCurrency($paid) }}</p>
        <p style="font-weight: bold; color: {{ ($order->total - $paid) > 0 ? '#e74c3c' : '#27ae60' }};">
            Balance Due: {{ getFormattedCurrency($order->total - $paid) }}
        </p>
    </div>

    <div style="text-align: center; border-top: 2px solid #eee; padding-top: 15px; color: #7f8c8d; font-size: 12px;">
        <p>Thank you for choosing {{ getApplicationName() }}!</p>
    </div>

</body>
</html>
