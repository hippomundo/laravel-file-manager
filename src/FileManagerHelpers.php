<?php

namespace RGilyov\FileManager;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

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
        try {
            return Storage::url($fileUrl);
        } catch (\Exception $e) {
            $disk = static::diskConfigurations();

            $url = Arr::get($disk, 'url');

            return $url ? static::glueParts($url, $fileUrl, true) : asset($fileUrl);
        }
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
