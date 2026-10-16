<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A school event delivered in-app and/or by email, as configured under
 * System settings → School notifications. Email text comes from the event's
 * email template when one is active, otherwise from the translation files.
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
        $locale = $notifiable->preferredLocale();
        $template = EmailTemplate::where('event', $this->event)->first();
        $tokens = collect($this->parameters + ['name' => $notifiable->name])->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => $value])->all();
        $subject = $template?->localized('subject', $locale) ?: __('notifications.subject.'.$this->event, [], $locale);
        $body = $template?->localized('body', $locale) ?: __('notifications.'.$this->event, $this->parameters, $locale);
        $message = (new MailMessage)->subject(strtr($subject, $tokens))->greeting(__('Hello :name', ['name' => $notifiable->name], $locale));
        foreach (preg_split('/\R/', strtr($body, $tokens)) as $line) {
            if (trim($line) !== '') {
                $message->line($line);
            }
        }

        return $message->action(__('Open', [], $locale), $this->url);
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $channel !== 'mail' || (EmailTemplate::where('event', $this->event)->value('is_active') ?? true);
    }
}
