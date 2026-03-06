<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Product;

class ProductRejectedNotification extends Notification
{
    use Queueable;

    public $product;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product, string $reason)
    {
        $this->product = $product;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'product_rejected',
            'title'       => 'Product Rejected',
            'message'     => "Your product '{$this->product->name_en}' was rejected. Reason: {$this->reason}.",
            'product_id'  => $this->product->id,
            'product_name'=> $this->product->name_en,
            'reason'      => $this->reason,
        ];
    }
}
