<?php

namespace App\Admin;

use App\Models\EmailTemplate;
use App\Models\Page;
use App\Models\SiteNotice;

class Resources
{
    public static function all(): array
    {
        return [
            'pages' => [Page::class, 'pages.manage', ['slug' => 'required|alpha_dash|max:100', 'title.en' => 'required|string|max:200', 'title.ar' => 'nullable|string|max:200', 'body.en' => 'required|string|max:50000', 'body.ar' => 'nullable|string|max:50000', 'is_published' => 'required|boolean']],
            'site-notices' => [SiteNotice::class, 'notices.manage', ['message.en' => 'required|string|max:500', 'message.ar' => 'nullable|string|max:500', 'link_url' => 'nullable|url:http,https|max:500', 'link_label.en' => 'nullable|string|max:100', 'link_label.ar' => 'nullable|string|max:100', 'tone' => 'required|in:info,success,warning,urgent', 'position' => 'required|in:above,below', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after:starts_at', 'is_active' => 'required|boolean', 'dismissible' => 'required|boolean', 'sort_order' => 'required|integer|min:0']],
            'email-templates' => [EmailTemplate::class, 'email_templates.manage', ['event' => 'required|in:welcome,support_received,security', 'subject.en' => 'required|string|max:200', 'subject.ar' => 'nullable|string|max:200', 'body.en' => 'required|string|max:5000', 'body.ar' => 'nullable|string|max:5000', 'is_active' => 'required|boolean']],
        ];
    }

    public static function get(string $name): array
    {
        abort_unless(isset(self::all()[$name]), 404);

        return self::all()[$name];
    }
}
