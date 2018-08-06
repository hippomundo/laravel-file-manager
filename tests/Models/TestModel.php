<?php

namespace Hippomundo\FileManager\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Hippomundo\FileManager\Models\File;
use Hippomundo\FileManager\Models\FileManager;
use Hippomundo\FileManager\Models\Media;
use Hippomundo\FileManager\Models\Video;

/**
 * Class TestModel
 * @package Hippomundo\FileManager\Test\Models
 */
class TestModel extends Model
{
    use FileManager;

    /**
     * @var string
     */
    protected $table = 'test_model';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'photo_id',
        'video_id',
        'file_id',
    ];

    /**
     * Key is relation method, 'request_binding' is request field,
     * 'data' is optional key, the data will be attached to media data inside your db,
     * 'config' rewrites default file configurations
     *
     * @return array
     */
    public function fileManagerOptions()
    {
        return [
            'photo' => [
                'config' =>
                    [
                        'image_size' => ['width' => 1000, 'height' => 500],
                        'thumbnail'  => ['width' => 100, 'height' => 100],
                        'directory'  => 'photos',
                        "update_names_on_change" => false
                    ]
            ],
            'video',
            'file',
            'photos',
            'videos',
            'files' => ['request_binding' => 'super_files'],
        ];
    }

    /**
     * @return string
     */
    public function fileManagerFolder()
    {
        return "{$this->name}_{$this->id}";
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function photo()
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function photos()
    {
        return $this->belongsToMany(Media::class, 'test_model_photos', 'test_model_id', 'photo_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function videos()
    {
        return $this->belongsToMany(Video::class, 'test_model_videos', 'test_model_id', 'video_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'test_model_files', 'test_model_id', 'file_id');
    }
}
