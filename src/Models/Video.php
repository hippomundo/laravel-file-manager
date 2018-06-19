<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\Interfaces\Mediable;

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
        'path',
        'thumbnail_path',
        'url',
        'thumbnail_url',
        "file_size",
        'folder_path',
        'original_name',
        'extension',
        'hash',
        'storage',
        'type'
    ];

    /**
     * @param $url
     * @return string
     */
    public function getUrlAttribute($url)
    {
        return asset($url);
    }

    /**
     * @param $url
     * @return string
     */
    public function getThumbnailUrlAttribute($url)
    {
        return asset($url);
    }

    /**
     * @return void
     */
    public function deleteVideo()
    {
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }
    }

    /**
     * @return void
     */
    public function deleteOriginal()
    {
        if (Storage::exists($this->original_path)) {
            Storage::delete($this->original_path);
        }
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
