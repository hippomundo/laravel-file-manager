<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\Interfaces\Mediable;

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
        "original_url",
        "path",
        "url",
        "storage",
        "thumbnail_path",
        "thumbnail_url",
        "status",
        'folder_path',
        'origin_name',
        'extension',
        'hash'
    ];

    /**
     * @param $url
     * @return string
     */
    public function getOriginalUrlAttribute($url)
    {
        return asset($url);
    }

    /**
     * @param $url
     * @return string
     */
    public function getUrlAttribute($url)
    {
        return asset($url);
    }

    /**
     * @param $thumbnail_url
     * @return string
     */
    public function getThumbnailUrlAttribute($thumbnail_url)
    {
        return asset($thumbnail_url);
    }

    /**
     * @return void
     */
    public function deleteFile()
    {
        if (Storage::exists($this->original_path)) {
            Storage::delete($this->original_path);
        }

        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }

        if (Storage::exists($this->thumbnail_path)) {
            Storage::delete($this->thumbnail_path);
        }
    }
}
