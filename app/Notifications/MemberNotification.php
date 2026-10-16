<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EVENTS = ['welcome', 'plan_changed', 'support_received', 'security'];

    public function __construct(public string $event, public array $parameters = [])
    {
        if (! in_array($event, self::EVENTS, true)) {
            throw new \InvalidArgumentException('Unknown notification event');
        }
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        if ($this->event === 'security') {
            return ['database', 'mail'];
        }
        $preferences = $notifiable->notification_preferences[$this->event] ?? ['database' => true, 'mail' => true];

        return array_keys(array_filter($preferences, fn ($enabled, $channel) => $enabled && in_array($channel, ['mail', 'database']), ARRAY_FILTER_USE_BOTH));
    }

    public function toArray(object $notifiable): array
    {
        return ['event' => $this->event, 'parameters' => $this->parameters];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::where('event', $this->event)->first();
        $subject = $template?->localized('subject') ?: __('notifications.'.$this->event);
        $body = $template?->localized('body') ?: __('notifications.'.$this->event);

        return (new MailMessage)->subject($subject)->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(str_replace('{name}', $notifiable->name, $body))->action(__('Open account'), url('/app'));
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $channel !== 'mail' || $this->event === 'security' || (EmailTemplate::where('event', $this->event)->value('is_active') ?? true);
    }
}
