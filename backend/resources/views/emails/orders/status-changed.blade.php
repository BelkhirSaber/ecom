<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .status-old {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-new {
            background-color: #d1fae5;
            color: #065f46;
        }
        .order-details {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Status Updated</h1>
    </div>
    
    <div class="content">
        <p>Hello,</p>
        
        <p>Your order <strong>#{{ $order->id }}</strong> status has been updated.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <span class="status-badge status-old">{{ ucfirst(str_replace('_', ' ', $oldStatus)) }}</span>
            <span style="font-size: 24px; margin: 0 10px;">→</span>
            <span class="status-badge status-new">{{ ucfirst(str_replace('_', ' ', $newStatus)) }}</span>
        </div>
        
        <div class="order-details">
            <h3>Order Details</h3>
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Total Amount:</strong> {{ number_format($order->grand_total, 2) }} {{ $order->currency ?? 'USD' }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
            <p><strong>Current Status:</strong> {{ ucfirst(str_replace('_', ' ', $newStatus)) }}</p>
        </div>
        
        @if($newStatus === 'shipped')
        <p>Your order has been shipped and is on its way to you!</p>
        @elseif($newStatus === 'delivered')
        <p>Your order has been delivered. We hope you enjoy your purchase!</p>
        @elseif($newStatus === 'cancelled')
        <p>Your order has been cancelled. If you have any questions, please contact our support team.</p>
        @elseif($newStatus === 'processing')
        <p>Your order is being processed and will be shipped soon.</p>
        @endif
        
        <p>If you have any questions about your order, please don't hesitate to contact us.</p>
        
        <p>Thank you for your business!</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
