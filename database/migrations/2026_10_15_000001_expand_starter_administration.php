<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name');
            $table->string('native_name');
            $table->string('direction', 3)->default('ltr');
            $table->unsignedTinyInteger('plural_forms')->default(2);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        DB::table('locales')->insert([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'plural_forms' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'plural_forms' => 6, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Schema::create('missing_translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 20);
            $table->string('key', 500);
            $table->string('fingerprint', 64)->unique();
            $table->timestamp('last_seen_at');
        });
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->index();
            $table->timestamp('provider_updated_at')->nullable();
        });
        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamp('created_at');
        });
        Schema::create('branding_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('disk');
            $table->string('path');
            $table->string('mime');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branding_assets');
        Schema::dropIfExists('webhook_receipts');
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_id']);
            $table->dropColumn(['provider_id', 'provider_updated_at']);
        });
        Schema::dropIfExists('missing_translations');
        Schema::dropIfExists('locales');
    }
};
