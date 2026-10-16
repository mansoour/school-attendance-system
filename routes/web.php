<?php

use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Auth\TwoFactorLoginController;
use App\Http\Controllers\BrandingAssetController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ResendWebhookController;
use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::post('/webhooks/resend', ResendWebhookController::class)->middleware('throttle:120,1');
Route::get('/branding-assets/{asset}', BrandingAssetController::class)->name('branding.asset');
Route::view('/contact', 'public.contact')->name('contact');
Route::post('/contact', [PublicController::class, 'contact'])->middleware('throttle:5,1');
Route::post('/locale', [PublicController::class, 'locale'])->name('locale');
Route::get('/pages/{slug}', [PublicController::class, 'page'])->name('pages.show');
Route::get('/api/v1/catalogs/{locale}', [PublicController::class, 'catalog'])->name('catalog');
Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorLoginController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorLoginController::class, 'store'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/account', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/account', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/account', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/account/security', [SecurityController::class, 'edit'])->name('security.edit');
    Route::post('/account/security/setup', [SecurityController::class, 'setup'])->middleware('password.confirm')->name('security.setup');
    Route::post('/account/security/confirm', [SecurityController::class, 'confirm'])->middleware(['password.confirm', 'throttle:5,1'])->name('security.confirm');
    Route::post('/account/security/challenge', [SecurityController::class, 'challenge'])->middleware('throttle:5,1')->name('security.challenge');
    Route::delete('/account/security', [SecurityController::class, 'destroy'])->middleware('password.confirm')->name('security.destroy');
    Route::post('/account/security/recovery', [SecurityController::class, 'recovery'])->middleware('password.confirm')->name('security.recovery');
    Route::get('/account/export', [MemberController::class, 'export'])->middleware(['verified', 'password.confirm', 'throttle:5,1'])->name('account.export');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => auth()->user()->can('school.access') ? redirect()->route('school.dashboard') : redirect()->route(auth()->user()->can('admin.access') ? 'admin.index' : 'profile.edit'))->name('dashboard');
    Route::redirect('/app', '/dashboard')->name('member');
    Route::prefix('/api/v1')->group(function () {
        Route::put('/locale', [PublicController::class, 'locale']);
        Route::get('/notifications', [MemberController::class, 'notifications']);
        Route::post('/notifications/read-all', [MemberController::class, 'readAll']);
        Route::post('/notifications/{id}/read', [MemberController::class, 'read']);
    });
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'can:admin.access', 'admin.mfa'])->group(function () {
    Route::get('/', [SystemController::class, 'index'])->name('index');
    Route::get('/configuration/{section}', [SettingsController::class, 'edit'])->name('settings');
    Route::post('/configuration/{section}', [SettingsController::class, 'save'])->middleware('password.confirm')->name('settings.save');
    Route::post('/configuration/{section}/reset', [SettingsController::class, 'reset'])->middleware('password.confirm')->name('settings.reset');
    Route::get('/integrations', [IntegrationController::class, 'index'])->middleware('can:integrations.manage')->name('integrations');
    Route::post('/integrations', [IntegrationController::class, 'save'])->middleware(['can:integrations.manage', 'password.confirm'])->name('integrations.save');
    Route::delete('/integrations', [IntegrationController::class, 'remove'])->middleware(['can:integrations.manage', 'password.confirm'])->name('integrations.remove');
    Route::post('/integrations/model', [IntegrationController::class, 'model'])->middleware(['can:integrations.manage', 'password.confirm'])->name('integrations.model');
    Route::post('/integrations/{provider}/test', [IntegrationController::class, 'test'])->middleware(['can:integrations.manage', 'password.confirm', 'throttle:5,1'])->name('integrations.test');
    Route::get('/support', [SystemController::class, 'support'])->middleware('can:support.access')->name('support');
    Route::patch('/support/{message}', [SystemController::class, 'resolve'])->middleware('can:support.access')->name('support.resolve');
    Route::get('/audit', [SystemController::class, 'audit'])->middleware('can:audit.view')->name('audit');
    Route::get('/emails', [SystemController::class, 'emails'])->middleware('can:emails.view')->name('emails');
    Route::get('/health', [SystemController::class, 'health'])->middleware('can:health.view')->name('health');
    Route::get('/translations/languages/{locale}', [TranslationController::class, 'edit'])->middleware('can:translations.edit')->name('translations.edit');
    Route::post('/languages', [TranslationController::class, 'create'])->middleware('can:translations.edit')->name('languages.create');
    Route::post('/languages/{locale}/toggle', [TranslationController::class, 'toggle'])->middleware('can:translations.publish')->name('languages.toggle');
    Route::get('/translations', [TranslationController::class, 'index'])->middleware('can:translations.edit')->name('translations');
    Route::post('/translations', [TranslationController::class, 'save'])->middleware('can:translations.edit')->name('translations.save');
    Route::post('/translations/{revision}/publish', [SystemController::class, 'publishTranslation'])->middleware('can:translations.publish')->name('translations.publish');
    Route::get('/translations/{revision}/export', [SystemController::class, 'exportTranslation'])->middleware('can:translations.edit')->name('translations.export');
    Route::post('/members/{user}/unlock', [AdminMemberController::class, 'unlock'])->middleware(['can:users.manage', 'password.confirm'])->name('members.unlock');
    Route::get('/members', [AdminMemberController::class, 'index'])->middleware('can:users.manage')->name('members');
    Route::post('/members/{user}/roles', [AdminMemberController::class, 'assignRole'])->middleware(['can:roles.manage', 'password.confirm'])->name('members.roles');
    Route::get('/roles', [AdminMemberController::class, 'roles'])->middleware('can:roles.manage')->name('roles');
    Route::post('/roles', [AdminMemberController::class, 'saveRole'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.save');
    Route::patch('/roles/{role}', [AdminMemberController::class, 'updateRole'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.update');
    Route::delete('/roles/{role}', [AdminMemberController::class, 'deleteRole'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.delete');
    Route::get('/manage/{resource}', [ResourceController::class, 'index'])->name('resources');
    Route::get('/manage/{resource}/edit/{id?}', [ResourceController::class, 'edit'])->name('resources.edit');
    Route::post('/manage/{resource}/{id?}', [ResourceController::class, 'save'])->name('resources.save');
    Route::delete('/manage/{resource}/{id}', [ResourceController::class, 'destroy'])->name('resources.delete');
});
require __DIR__.'/school.php';
require __DIR__.'/auth.php';
