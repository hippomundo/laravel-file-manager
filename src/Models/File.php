<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\Interfaces\Mediable;

/**
 * Class File
 * @package RGilyov\FileManager\Models
 */
class File extends Model implements Mediable
{
    /**
     * @var string
     */
    protected $table = 'files';

    /**
     * @var array
     */
    protected $fillable = [
        'path',
        'url',
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
     * @return void
     */
    public function deleteFile()
    {
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }
    }
}