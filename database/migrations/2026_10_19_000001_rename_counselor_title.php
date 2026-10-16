<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The counselor role is now titled "الموجهة الطلابية". Updates page text that
 * still uses the previous title; the role label itself comes from lang files.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('pages')->get(['id', 'body']) as $page) {
            $body = json_decode((string) $page->body, true);
            if (! is_array($body)) {
                continue;
            }
            $updated = array_map(fn ($text) => is_string($text) ? str_replace('المرشدة الطلابية', 'الموجهة الطلابية', $text) : $text, $body);
            if ($updated !== $body) {
                DB::table('pages')->where('id', $page->id)->update(['body' => json_encode($updated, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
