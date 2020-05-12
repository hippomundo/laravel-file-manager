<?php

namespace Hippomundo\FileManager;

use Illuminate\Support\Arr;
use Hippomundo\FileManager\Models\File;
use Hippomundo\FileManager\Models\Media;
use Hippomundo\FileManager\Models\Video;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FileManagerHelpers
 * @package Hippomundo\FileManager
 */
class FileManagerHelpers
{
    /**
     * @return bool
     */
    public static function isCloud()
    {
        $disk = static::diskConfigurations();

        return static::isCloudDisk($disk);
    }

    /**
     * @return bool
     */
    public static function isBackUpDiskCloud()
    {
        $disk = static::backUpDiskConfigurations();

        return static::isCloudDisk($disk);
    }

    /**
     * @param $driverSettings
     * @return bool
     */
    public static function isCloudDisk($driverSettings)
    {
        $driver = Arr::get($driverSettings, 'driver');

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
     * @return bool
     */
    public static function serveFilesFromBackUp()
    {
        return ( bool )config('file-manager.serve_files_from_backup_disk');
    }

    /**
     * @param Model $model
     * @return bool
     */
    public static function isMedia(Model $model)
    {
        return ($model instanceof Media || $model instanceof Video || $model instanceof File);
    }
}
