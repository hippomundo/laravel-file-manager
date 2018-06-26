<?php

namespace RGilyov\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RGilyov\FileManager\FileManagerHelpers;
use RGilyov\FileManager\Interfaces\Mediable;

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
        "original_url",
        "path",
        "url",
        "storage",
        "thumbnail_path",
        "thumbnail_url",
        "status",
        'folder_path',
        'original_name',
        'extension',
        'hash'
    ];

    /**
     * @param $url
     * @return string
     */
    public function getOriginalUrlAttribute($url)
    {
        return FileManagerHelpers::fileUrl($url);
    }

    /**
     * @param $url
     * @return string
     */
    public function getUrlAttribute($url)
    {
        return FileManagerHelpers::fileUrl($url);
    }

    /**
     * @param $thumbnail_url
     * @return string
     */
    public function getThumbnailUrlAttribute($thumbnail_url)
    {
        return FileManagerHelpers::fileUrl($thumbnail_url);
    }

    /**
     * @return void
     */
    public function deleteImage()
    {
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }
    }

    /**
     * @return void
     */
    public function deleteThumbnail()
    {
        if (Storage::exists($this->thumbnail_path)) {
            Storage::delete($this->thumbnail_path);
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
        $this->deleteOriginal();
        $this->deleteImage();
        $this->deleteThumbnail();
    }
}
