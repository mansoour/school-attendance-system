<?php

namespace App\Operations;

use App\Settings\SecretStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SqliteBackup
{
    public function create(): string
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $name = 'backups/'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(6));
        $snapshot = $disk->path($name.'.sqlite');
        $password = app(SecretStore::class)->get('backup_archive_password');
        try {
            DB::statement("VACUUM INTO '".str_replace("'", "''", $snapshot)."'");
            $pdo = new \PDO('sqlite:'.$snapshot);
            $valid = $pdo->query('PRAGMA integrity_check')->fetchColumn() === 'ok';
            $pdo = null;
            throw_unless($valid, new \RuntimeException('Backup integrity check failed.'));
            if (! $password) {
                return $name.'.sqlite';
            }
            $zip = new ZipArchive;
            throw_unless($zip->open($disk->path($name.'.zip'), ZipArchive::CREATE | ZipArchive::EXCL) === true, new \RuntimeException('Cannot create backup archive.'));
            $zip->setPassword($password);
            $zip->addFile($snapshot, 'database.sqlite');
            if (! $zip->setEncryptionName('database.sqlite', ZipArchive::EM_AES_256)) {
                $zip->close();
                throw new \RuntimeException('AES-256 encryption unavailable.');
            }
            throw_unless($zip->close(), new \RuntimeException('Cannot finish backup archive.'));

            return $name.'.zip';
        } catch (\Throwable $exception) {
            $disk->delete([$name.'.sqlite', $name.'.zip']);
            throw $exception;
        } finally {
            if ($password && is_file($snapshot)) {
                unlink($snapshot);
            }
        }
    }
}
