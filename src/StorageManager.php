<?php

namespace RGilyov\FileManager;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
     * @var string
     */
    const TMP_DISK = 'local';

    /**
     * @var string
     */
    const TMP_DIR = 'tmp_files';

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
     * If we using cloud driver we need to use
     * local disk in order to create local copies
     * of files to be able to resize and rotate them.
     *
     * @var FilesystemAdapter
     */
    protected static $tmp_disk;

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
            static::BACKUP_DISK => static::hasBackUpDisk() ? Storage::disk(static::$backup_disk) : null
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
     * @return bool
     */
    public static function hasBackUpDisk()
    {
        return ! is_null(static::$backup_disk);
    }

    /**
     * @return string
     */
    public static function getDiskName()
    {
        return static::$main_disk;
    }

    /**
     * @return FilesystemAdapter
     */
    public static function getTmpDisk()
    {
        if (! is_null(static::$tmp_disk)) {
            return static::$tmp_disk;
        }

        return static::$tmp_disk = Storage::disk(static::TMP_DISK);
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

        return $disks[$name];
    }

    /**
     * @param $path
     * @return bool
     */
    public static function exists($path)
    {
        $disks = static::getDisks();

        /** @var $disk FilesystemAdapter */

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

    /**
     * @param $path
     */
    public static function deleteDirectory($path)
    {
        $disks = static::getDisks();

        /** @var $disk FilesystemAdapter */

        foreach ($disks as $disk) {
            if ($disk->exists($path)) {
                $disk->deleteDirectory($path);
            }
        }
    }

    /**
     * @param $path
     * @param $contents
     * @param null $visibility
     */
    public static function put($path, $contents, $visibility = null)
    {
        $disks = static::getDisks();

        /** @var $disk FilesystemAdapter */

        foreach ($disks as $disk) {
            $disk->put($path, $contents, $visibility);
        }
    }

    /**
     * @param $path
     * @return string
     * @throws FileNotFoundException
     */
    public static function get($path)
    {
        $disks = static::getDisks();

        /** @var $disk FilesystemAdapter */

        foreach ($disks as $disk) {
            if ($disk->exists($path)) {
                return $disk->get($path);
            }
        }

        throw new FileNotFoundException();
    }

    /**
     * @param $from
     * @param $to
     */
    public static function move($from, $to)
    {
        $disks = static::getDisks();

        /** @var $disk FilesystemAdapter */

        foreach ($disks as $disk) {
            if ($disk->exists($from)) {
                $disk->move($from, $to);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Path building
    |--------------------------------------------------------------------------
    */

    /**
     * @param UploadedFile $file
     * @return string
     */
    public static function originalName(UploadedFile $file)
    {
        return Str::slug(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_'
            ) . "." . static::extension($file);
    }

    /**
     * @param $path
     * @return string
     */
    public static function fileName($path)
    {
        if ($path instanceof UploadedFile) {
            $path = static::originalName($path);
        }

        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * @param $path
     * @return string
     */
    public static function baseName($path)
    {
        if ($path instanceof UploadedFile) {
            $path = static::originalName($path);
        }

        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * @param $path
     * @return string
     */
    public static function dirName($path)
    {
        if ($path instanceof UploadedFile) {
            $path = static::originalName($path);
        }

        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * @param $path
     * @return mixed
     */
    public static function extension($path)
    {
        if ($path instanceof UploadedFile) {
            $path = $path->getClientOriginalName();
        }

        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @param $path
     * @param null $dir
     * @return string
     */
    public static function generateUniquePath($path, $dir = null)
    {
        $dirName = $dir ?: static::dirName($path);

        $extension = static::extension($path);

        $name = Str::slug(Str::random(), '_') . "." . $extension;

        $uniquePath = FileManagerHelpers::glueParts($dirName, $name);

        if (static::exists($uniquePath)) {
            return static::generateUniquePath($uniquePath);
        }

        return $uniquePath;
    }

    /*
    |--------------------------------------------------------------------------
    | TMP files
    |--------------------------------------------------------------------------
    */

    /**
     * @param $path
     * @return string
     */
    public static function generateTmpPath($path)
    {
        return static::generateUniquePath(
            FileManagerHelpers::glueParts(static::TMP_DIR, static::baseName($path))
        );
    }

    /**
     * @param $path
     * @return string
     */
    public static function tmpFullPath($path)
    {
        return FileManagerHelpers::glueParts(static::tmpFullPathPrefix(), $path);
    }

    /**
     * @param $path
     * @return string
     */
    public static function removeTmpPrefixFromPath($path)
    {
        return ltrim(str_replace(static::tmpFullPathPrefix(), '', $path), DIRECTORY_SEPARATOR);
    }

    /**
     * @return string
     */
    public static function tmpFullPathPrefix()
    {
        return static::getTmpDisk()->getDriver()->getAdapter()->getPathPrefix();
    }

    /**
     * @param $path
     * @return mixed
     * @throws FileNotFoundException
     */
    public static function makeTmpFile($path)
    {
        if ($path instanceof UploadedFile) {
            return $path;
        }

        $contents = static::get($path);

        $tmpPath = static::generateTmpPath($path);

        static::getTmpDisk()->put($tmpPath, $contents);

        return static::tmpFullPath($tmpPath);
    }

    /**
     * @param $path
     * @return bool
     */
    public static function deleteTmpFile($path)
    {
        if (! is_string($path)) {
            return false;
        }

        $path = static::removeTmpPrefixFromPath($path);

        $disk = static::getTmpDisk();

        if (strpos($path, static::TMP_DIR) === 0) {
            if ($disk->exists($path)) {
                return $disk->delete($path);
            }
        }

        return false;
    }

    /**
     * @param $path
     * @param callable $callback
     * @return mixed
     * @throws FileNotFoundException
     */
    public static function tmpScope($path, callable $callback)
    {
        $tmp = static::makeTmpFile($path);

        $result = $callback($tmp);

        static::deleteTmpFile($tmp);

        return $result;
    }
}