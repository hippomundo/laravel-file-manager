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
     * @param $fileUrl
     * @return string
     */
    public static function fileUrl($fileUrl)
    {
        $disk = static::diskConfigurations();

        $url = Arr::get($disk, 'url');

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
}
