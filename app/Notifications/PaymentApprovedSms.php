<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentApprovedSms extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->payment->payment_amount, 2);
        $purpose = $this->payment->payment_purpose->value;

        $message = "Your payment has been approved.\n";
        $message .= "Amount: BDT {$amount}\n";
        $message .= "Purpose: {$purpose}\n";
        $message .= 'Thank you.';

        return $message;
    }
}
