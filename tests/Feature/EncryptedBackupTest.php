<?php

namespace Tests\Feature;

use App\Operations\SqliteBackup;
use App\Settings\SecretStore;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class EncryptedBackupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backup_is_encrypted_and_does_not_leave_plaintext_snapshot(): void
    {
        Storage::fake('local');
        app(SecretStore::class)->put('backup_archive_password', 'an-independent-backup-password');
        $file = app(SqliteBackup::class)->create();
        $this->assertStringEndsWith('.zip', $file);
        $this->assertSame([$file], Storage::disk('local')->files('backups'));
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($file));
        $this->assertSame(ZipArchive::EM_AES_256, $zip->statName('database.sqlite')['encryption_method']);
        $this->assertFalse($zip->getFromName('database.sqlite'));
        $zip->setPassword('an-independent-backup-password');
        $this->assertStringStartsWith('SQLite format 3', $zip->getFromName('database.sqlite'));
        $zip->close();
    }
}
