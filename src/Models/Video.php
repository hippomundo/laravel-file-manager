<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\StorageManager;

/**
 * Class Video
 * @package RGilyov\FileManager\Models
 */
class Video extends Model implements Mediable
{
    /**
     * @var string
     */
    protected $table = 'videos';

    /**
     * @var array
     */
    protected $fillable = [
        'original_path',
        'path',
        'folder_path',
        'original_name',
        'extension',
        'hash',
        'storage',
        'type',
        'file_size'
    ];

    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        return StorageManager::url($this->path);
    }

    /**
     * @return string
     */
    public function getThumbnailUrlAttribute()
    {
        return StorageManager::url($this->thumbnail_path);
    }

    /**
     * @return void
     */
    public function deleteVideo()
    {
        StorageManager::delete($this->path);
    }

    /**
     * @return void
     */
    public function deleteOriginal()
    {
        StorageManager::originalUrl($this->original_path);
    }

    /**
     * @return void
     */
    public function deleteFile()
    {
        $this->deleteVideo();
        $this->deleteOriginal();
    }
}
