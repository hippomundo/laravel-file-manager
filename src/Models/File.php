<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use RGilyov\FileManager\FileManagerHelpers;
use RGilyov\FileManager\Interfaces\Mediable;
use RGilyov\FileManager\StorageManager;

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
        return FileManagerHelpers::fileUrl($url);
    }

    /**
     * @return void
     */
    public function deleteFile()
    {
        StorageManager::delete($this->path);
    }
}