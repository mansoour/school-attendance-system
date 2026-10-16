<?php

use App\Support\DefaultContent;
use Illuminate\Database\Migrations\Migration;

/**
 * Fills the Help, Privacy and Terms pages, adds email templates for every
 * notification event and a welcome site notice, and turns all school
 * notifications on (in-app and email). Content edited by staff is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        DefaultContent::install();
        DefaultContent::enableNotifications();
    }

    public function down(): void
    {
        //
    }
};
