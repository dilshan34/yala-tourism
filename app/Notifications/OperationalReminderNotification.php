<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalReminderNotification extends Notification
{
    use Queueable;

    public string $type;
    public int $serviceId;
    public string $referenceNo;
    public string $message;

    public function __construct(string $type, int $serviceId, string $referenceNo, string $message)
    {
        $this->type = $type;
        $this->serviceId = $serviceId;
        $this->referenceNo = $referenceNo;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'service_id' => $this->serviceId,
            'reference_no' => $this->referenceNo,
            'message' => $this->message,
            'url' => $this->type === 'safari' ? route('safaris.show', $this->serviceId) : route('transports.show', $this->serviceId),
        ];
    }
}
