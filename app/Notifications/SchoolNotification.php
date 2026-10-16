<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A school event delivered in-app and/or by email, as configured under
 * Administration → School notifications. Text is rendered in the recipient's language.
 */
class SchoolNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     * @param  array<string, string>  $parameters
     */
    public function __construct(public string $event, public array $parameters, public string $url, public array $channels)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => $this->event, 'parameters' => $this->parameters, 'url' => $this->url];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = __('notifications.'.$this->event, $this->parameters, $notifiable->preferredLocale());

        return (new MailMessage)->subject(__('notifications.subject.'.$this->event, [], $notifiable->preferredLocale()))
            ->greeting(__('Hello :name', ['name' => $notifiable->name], $notifiable->preferredLocale()))
            ->line($message)->action(__('Open', [], $notifiable->preferredLocale()), $this->url);
    }
}
