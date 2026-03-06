<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class OrderStatusChangedAdminNotification extends Notification
{
    use Queueable;

    public $order;
    public $changedBy; // 'seller' or 'admin'

    public function __construct(Order $order, string $changedBy = 'seller')
    {
        $this->order = $order;
        $this->changedBy = $changedBy;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $storeName = $this->order->seller->store_name_en ?? 'Seller';
        $status    = strtoupper($this->order->status);
        $orderId   = $this->order->id;

        if ($this->changedBy === 'seller') {
            // Message for the super admin
            $title   = "Order #{$orderId} Status Updated by Seller";
            $message = "{$storeName} marked order #{$orderId} as {$status}.";
        } else {
            // Message for the seller (admin changed the status)
            $title   = "Your Order #{$orderId} Status Changed";
            $message = "Admin updated order #{$orderId} to {$status}.";
        }

        return [
            'type'      => 'order_status_changed',
            'title'     => $title,
            'message'   => $message,
            'order_id'  => $orderId,
            'status'    => $this->order->status,
            'changed_by'=> $this->changedBy,
        ];
    }
}
