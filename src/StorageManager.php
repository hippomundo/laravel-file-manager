<?php

namespace RGilyov\FileManager;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Class StorageManager
 * @package RGilyov\FileManager
 */
class StorageManager
{
    /**
     * @var string
     */
    const MAIN_DISK = 'main_disk';

    /**
     * @var string
     */
    const BACKUP_DISK = 'backup_disk';

    /**
     * @var array
     */
    protected static $disks = [];

    /**
     * @var string
     */
    protected static $backup_disk;

    /**
     * @var string
     */
    protected static $main_disk;

    /**
     * @return array
     */
    protected static function getDisks()
    {
        if (! empty(static::$disks)) {
            return static::$disks;
        }

        static::$backup_disk = FileManagerHelpers::getBackUpDiskName();

        static::$main_disk = FileManagerHelpers::diskName();

        return static::$disks = array_filter([
            static::MAIN_DISK   => Storage::disk(static::$main_disk),
            static::BACKUP_DISK => static::getBackUpDisk() ? Storage::disk(static::$backup_disk) : null
        ]);
    }

    /**
     * @return string
     */
    public static function getBackUpDisk()
    {
        return static::$backup_disk;
    }

    /**
     * @return string
     */
    public static function getDiskName()
    {
        return static::$main_disk;
    }

    /**
     * @param $name
     * @return string
     */
    protected static function resolveDiskName($name)
    {
        return ($name && static::diskExists($name)) ? $name : static::MAIN_DISK;
    }

    /**
     * @param $name
     * @return bool
     */
    public static function diskExists($name)
    {
        $disks = static::getDisks();

        return isset($disks[$name]);
    }

    /**
     * @param null $name
     * @return FilesystemAdapter
     */
    public static function getDisk($name = null)
    {
        $name = static::resolveDiskName($name);

        $disks = static::getDisks();

        return Arr::get($disks, $name);
    }

    /**
     * @param $path
     * @return bool
     */
    public static function exists($path)
    {
        $disks = static::getDisks();

        foreach ($disks as $disk) {
            if ($disk->exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $path
     * @return void
     */
    public static function delete($path)
    {
        $disks = static::getDisks();

        /** @var $disk FilesystemAdapter */

        foreach ($disks as $disk) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
