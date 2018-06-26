<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\FileManagerHelpers;
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
        'original_path',
        'original_url',
        'path',
        'url',
        'folder_path',
        'original_name',
        'extension',
        'hash',
        'storage',
        'type',
        'file_size'
    ];

    /**
     * @param $url
     * @return string
     */
    public function getUrlAttribute($url)
    {
        return FileManagerHelpers::fileUrl($url);
    }

    /**
     * @param $url
     * @return string
     */
    public function getThumbnailUrlAttribute($url)
    {
        return FileManagerHelpers::fileUrl($url);
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
