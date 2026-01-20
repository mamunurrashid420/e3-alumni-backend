<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MembershipApprovedSms extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $password,
        public string $memberId
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // For now, just log the SMS content since no SMS service is configured
        // To enable actual SMS sending, configure a service like Twilio or Vonage
        // and add 'vonage' or 'twilio' to the array below
        return ['database'];
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     */
    public function toVonage(object $notifiable): VonageMessage
    {
        $message = "Your membership application has been approved!\n\n";
        $message .= "Member ID: {$this->memberId}\n";
        $message .= "Password: {$this->password}\n\n";
        $message .= "Please login using your phone number and this password.";

        return (new VonageMessage)
            ->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = "Your membership application has been approved!\n\n";
        $message .= "Member ID: {$this->memberId}\n";
        $message .= "Password: {$this->password}\n\n";
        $message .= "Please login using your phone number and this password.";

        // Log the SMS content for now (since no SMS service is configured)
        Log::info('SMS Notification (Membership Approved)', [
            'phone' => $notifiable->phone,
            'member_id' => $this->memberId,
            'message' => $message,
        ]);

        return [
            'type' => 'membership_approved',
            'member_id' => $this->memberId,
            'message' => $message,
        ];
    }
}
