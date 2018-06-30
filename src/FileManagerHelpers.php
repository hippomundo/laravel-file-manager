<?php

namespace RGilyov\FileManager;

use Illuminate\Support\Arr;

/**
 * Class FileManagerHelpers
 * @package RGilyov\FileManager
 */
class FileManagerHelpers
{
    /**
     * @return bool
     */
    public static function isCloud()
    {
        $disk = static::diskConfigurations();

        $driver = Arr::get($disk, 'driver');

        return strcasecmp($driver, 'local') !== 0;
    }

    /**
     * @return mixed|null
     */
    public static function getBackUpDiskName()
    {
        $backupDiskName = config('file-manager.backup_disk');

        $configurations = config("filesystems.disks.{$backupDiskName}");

        if (is_null($configurations)) {
            return null;
        }

        $mainDiskName = static::diskName();

        return ($mainDiskName === $backupDiskName) ? null : $backupDiskName;
    }

    /**
     * @return mixed
     */
    public static function diskName()
    {
        return config("filesystems.default");
    }

    /**
     * @return mixed
     */
    public static function diskConfigurations()
    {
        $diskName = static::diskName();

        return config("filesystems.disks.{$diskName}");
    }

    /**
     * @return mixed|null
     */
    public static function backUpDiskConfigurations()
    {
        $backupDiskName = static::getBackUpDiskName();

        if (! $backupDiskName) {
            return null;
        }

        return config("filesystems.disks.{$backupDiskName}");
    }

    /**
     * @param $path
     * @return string
     */
    public static function fileUrl($path)
    {
        $fileUrl = static::pathToUrl($path);

        if (
            StorageManager::hasBackUpDisk()
            && ! StorageManager::getDisk(StorageManager::MAIN_DISK)->exists($path)
        ){
            $backupDisk = StorageManager::getDisk(StorageManager::BACKUP_DISK);

            if (method_exists($backupDisk, 'url')) {

            }

            $configurations = static::backUpDiskConfigurations();
        } else {
            $configurations = static::diskConfigurations();
        }

        $url = Arr::get($configurations, 'url');

        return $url ? static::glueParts($url, $fileUrl, true) : asset($fileUrl);
    }

    /**
     * @param $part1
     * @param $part2
     * @param bool $url
     * @return string
     */
    public static function glueParts($part1, $part2, $url = false)
    {
        $sep = $url ? '/' : DIRECTORY_SEPARATOR;

        return rtrim($part1, $sep) . $sep . ltrim($part2, $sep);
    }

    /**
     * @param $path
     * @return mixed
     */
    public static function pathToUrl($path)
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }
}
