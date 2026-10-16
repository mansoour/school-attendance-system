<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('locale')->default('en');
            $t->timestamp('terms_accepted_at')->nullable();
            $t->boolean('marketing_opt_in')->default(false);
            $t->timestamp('marketing_opt_in_at')->nullable();
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->unsignedBigInteger('two_factor_last_step')->nullable();
            $t->unsignedInteger('failed_login_attempts')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->json('notification_preferences')->nullable();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value');
            $t->timestamps();
        });
        Schema::create('integration_secrets', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action');
            $t->string('target_type')->nullable();
            $t->string('target_id')->nullable();
            $t->string('result');
            $t->json('metadata')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->json('name');
            $t->json('description')->nullable();
            $t->boolean('is_free')->default(true);
            $t->json('prices')->nullable();
            $t->json('discount')->nullable();
            $t->string('currency', 3)->default('USD');
            $t->boolean('is_default')->default(false);
            $t->boolean('is_public')->default(true);
            $t->boolean('on_sale')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('features', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->json('name');
            $t->json('description')->nullable();
            $t->boolean('is_enabled')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('feature_plan', function (Blueprint $t) {
            $t->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('allowance')->nullable();
            $t->primary(['feature_id', 'plan_id']);
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained()->restrictOnDelete();
            $t->string('status')->default('active');
            $t->string('source')->default('manual');
            $t->timestamp('starts_at');
            $t->timestamp('ends_at')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('feature_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('feature_code');
            $t->string('period');
            $t->unsignedInteger('used')->default(0);
            $t->unique(['user_id', 'feature_code', 'period']);
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('topic');
            $t->text('message');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('title');
            $t->json('body');
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });
        Schema::create('site_notices', function (Blueprint $t) {
            $t->id();
            $t->json('message');
            $t->json('link_label')->nullable();
            $t->string('link_url')->nullable();
            $t->string('tone')->default('info');
            $t->string('position')->default('above');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(false);
            $t->boolean('dismissible')->default(true);
            $t->integer('sort_order')->default(0);
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('email_templates', function (Blueprint $t) {
            $t->id();
            $t->string('event')->unique();
            $t->json('subject');
            $t->json('body');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('email_logs', function (Blueprint $t) {
            $t->id();
            $t->string('recipient');
            $t->string('subject');
            $t->string('status')->default('sent');
            $t->timestamps();
        });
        Schema::create('translation_revisions', function (Blueprint $t) {
            $t->id();
            $t->string('locale');
            $t->json('messages');
            $t->timestamp('published_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['translation_revisions', 'email_logs', 'email_templates', 'site_notices', 'pages', 'contact_messages', 'notifications', 'feature_usages', 'subscriptions', 'feature_plan', 'features', 'plans', 'audit_logs', 'integration_secrets', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['locale', 'terms_accepted_at', 'marketing_opt_in', 'marketing_opt_in_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_step', 'failed_login_attempts', 'locked_until', 'notification_preferences']));
    }
};
