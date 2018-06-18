<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\Interfaces\Mediable;

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
        'origin_name',
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
    public function deleteFile()
    {
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }
    }
}
