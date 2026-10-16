<?php

namespace App\Notifications;

use App\Models\User;

class SecurityNotice
{
    public static function send(User $user, string $event, array $parameters = []): void
    {
        $user->notify(new MemberNotification('security', ['action' => $event] + $parameters));
    }
}
