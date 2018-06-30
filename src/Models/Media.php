<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use RGilyov\FileManager\FileManagerHelpers;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\StorageManager;

/**
 * Class Media
 * @package RGilyov\FileManager\Models
 */
class Media extends Model implements Mediable
{
    /**
     * @var string
     */
    protected $table = 'media';

    /**
     * @var array
     */
    protected $fillable = [
        "type",
        "file_size",
        "original_path",
        "path",
        "storage",
        "thumbnail_path",
        "status",
        'folder_path',
        'original_name',
        'extension',
        'hash'
    ];

    /**
     * @return string
     */
    public function getOriginalUrlAttribute()
    {
        return FileManagerHelpers::fileUrl($this->original_path);
    }

    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        return FileManagerHelpers::fileUrl($this->path);
    }

    /**
     * @return string
     */
    public function getThumbnailUrlAttribute()
    {
        return FileManagerHelpers::fileUrl($this->thumbnail_path);
    }

    /**
     * @return void
     */
    public function deleteImage()
    {
        StorageManager::delete($this->path);
    }

    /**
     * @return void
     */
    public function deleteThumbnail()
    {
        StorageManager::delete($this->thumbnail_path);
    }

    /**
     * @return void
     */
    public function deleteOriginal()
    {
        StorageManager::delete($this->original_path);
    }

    /**
     * @return void
     */
    public function deleteFile()
    {
        $this->deleteOriginal();
        $this->deleteImage();
        $this->deleteThumbnail();
    }
}
