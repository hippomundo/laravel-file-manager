<?php

namespace Hippomundo\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Hippomundo\FileManager\Interfaces\Mediable;
use Hippomundo\FileManager\StorageManager;

/**
 * Class File
 * @package Hippomundo\FileManager\Models
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
        "file_size",
        'folder_path',
        'original_name',
        'extension',
        'hash',
        'storage',
        'type'
    ];

    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        return StorageManager::url($this->path);
    }

    /**
     * @return void
     */
    public function deleteFile()
    {
        StorageManager::delete($this->path);
    }
}
