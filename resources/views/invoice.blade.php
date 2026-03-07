<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 30px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: table;
        }
        .header-left {
            display: table-cell;
            width: 50%;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: bottom;
        }
        h1 {
            color: #555;
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        .info-section {
            width: 100%;
            display: table;
            margin-bottom: 40px;
        }
        .info-block {
            display: table-cell;
            width: 50%;
        }
        .info-title {
            font-size: 14px;
            color: #777;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: bold;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background-color: #f9fafb;
            color: #555;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #eee;
            font-size: 14px;
        }
        table.items th.right {
            text-align: right;
        }
        table.items td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        table.items td.right {
            text-align: right;
        }
        .totals {
            width: 100%;
            display: table;
        }
        .totals-space {
            display: table-cell;
            width: 50%;
        }
        .totals-table {
            display: table-cell;
            width: 50%;
        }
        .totals-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            font-size: 14px;
        }
        .totals-table td.label {
            text-align: right;
            color: #555;
            font-weight: bold;
        }
        .totals-table td.value {
            text-align: right;
            width: 30%;
        }
        .totals-table tr.grand-total td {
            border-top: 2px solid #eee;
            font-size: 18px;
            font-weight: bold;
            color: #111;
            padding-top: 15px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #888;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <div class="company-name">E-Commerce Platform</div>
            <div>safqa.support@example.com</div>
        </div>
        <div class="header-right">
            <h1>INVOICE</h1>
            <div><strong>Invoice No:</strong> #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div><strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}</div>
            <div><strong>Status:</strong> <span style="text-transform: capitalize;">{{ $order->status }}</span></div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-block">
            <div class="info-title">Bill To:</div>
            <div><strong>{{ $order->customer->name ?? 'Customer Name' }}</strong></div>
            <div>{{ $order->customer->email ?? '' }}</div>
            @if($order->address)
                <div>{{ $order->address->name }}</div>
                <div>{{ $order->address->street }}, {{ $order->address->district }}</div>
                @if($order->address->building)<div>Building: {{ $order->address->building }}</div>@endif
                <div>{{ $order->address->city }}, {{ $order->address->country }}</div>
                <div>Phone: {{ $order->address->phone }}</div>
            @else
                <div>Address not provided</div>
            @endif
        </div>
        <div class="info-block" style="text-align: right;">
            @if(isset($isSellerInvoice) && $isSellerInvoice && $order->seller)
                <div class="info-title">Sold By:</div>
                <div><strong>{{ $order->seller->store_name ?? $order->seller->user->name }}</strong></div>
                <div>{{ $order->seller->user->email ?? '' }}</div>
            @endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item Description</th>
                @if(isset($isSellerInvoice) && $isSellerInvoice)
                    <!-- Only show status on seller invoice -->
                    <th>Status</th>
                @endif
                <th style="width: 100px;">Price</th>
                <th style="width: 50px; text-align: center;">Qty</th>
                <th class="right" style="width: 100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->variant_name)
                        <br><span style="color: #666; font-size: 12px;">Variant: {{ $item->variant_name }}</span>
                    @endif
                </td>
                @if(isset($isSellerInvoice) && $isSellerInvoice)
                    <td style="text-transform: capitalize;">{{ $item->status }}</td>
                @endif
                <td>${{ number_format($item->price, 2) }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td class="right">${{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-space">
            @if($order->notes)
                <div class="info-title" style="margin-top: 20px;">Order Notes:</div>
                <div style="font-size: 13px; color: #555;">{{ $order->notes }}</div>
            @endif
        </div>
        <div class="totals-table">
            <table>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">${{ number_format($calculatedSubtotal ?? $order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0 && (!isset($isSellerInvoice) || !$isSellerInvoice))
                <tr>
                    <td class="label">Discount:</td>
                    <td class="value">-${{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($order->tax_amount > 0 && (!isset($isSellerInvoice) || !$isSellerInvoice))
                <tr>
                    <td class="label">Tax:</td>
                    <td class="value">${{ number_format($order->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td class="label">Total Amount:</td>
                    <td class="value">${{ number_format($calculatedTotal ?? $order->total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Thank you for your business! <br>
        If you have any questions about this invoice, please contact support.
    </div>

</body>
</html>
