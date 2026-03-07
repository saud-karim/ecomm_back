<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Product;

class NewProductSubmittedNotification extends Notification
{
    use Queueable;

    public $product;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
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
            'type'        => 'new_product_submitted',
            'title'       => 'New Product Submitted',
            'message'     => "Seller '{$this->product->seller->store_name_en}' submitted a new product ('{$this->product->name_en}') for approval.",
            'product_id'  => $this->product->id,
            'product_name'=> $this->product->name_en,
            'product_name_ar'=> $this->product->name_ar,
            'seller_name' => $this->product->seller->store_name_en,
            'seller_name_ar'=> $this->product->seller->store_name_ar,
        ];
    }
}
