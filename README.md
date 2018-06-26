# laravel-file-manager
The best way to upload, store and manage your files.

## Installation ##

```php
composer require rgilyov/laravel-file-manager
```

Register \RGilyov\CsvImporter\CsvImporterServiceProvider inside `config/app.php`
```php
    'providers' => [
        //...
        \RGilyov\FileManager\Providers\FileManagerServiceProvider::class,
    ];
```

After installation you may publish default configuration file
and migrations if you use laravel 5.2 and < version
```
php artisan vendor:publish
```

Migrate all needed tables for the package
```
php artisan migrate
```

## Basic usage ##

Before start we need to implement `RGilyov\FileManager\Models\FileManager`
trait into your model:

```
    use RGilyov\FileManager\Models\FileManager;
```

Then we need to declare relationship between
file class and your model:

There are two possible relationships `belongsTo` and `belongsToMany`

```
    public function photo()
    {
        return $this->belongsTo(RGilyov\FileManager\Models\Media::class, 'photo_id');
    }
    
    public function videos()
    {
        return $this->belongsToMany(RGilyov\FileManager\Models\Video::class, 'your_models_videos');
    }
    
    public function file()
    {
        return $this->belongsTo(RGilyov\FileManager\Models\File::class);
    }
```

Then we need to declare bindings among the methods and request values, attributes

```
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
                            "update_file_names_on_change" => false
                        ]
                ],
                'file',
                'videos' => ['request_binding' => 'my_videos']
            ];
        }
```

Done. So now, when we will try to update or create the model the files
which was given as attributes for the model, will be created, resized
and attached to the model automatically:

```
    $attrs = [
        'model_name' => 'name',
        'file' => UploadedFile $file,
        'my_videos' => [UploadedFile $video, UploadedFile $video],
        'photo' => UploadedFile $photo
    ];
    
    $someModel = App\SomeModel::create($attrs);
```

There are also some helper functions that will allow you to
manipulate uploaded files:

```
    $someModel->fileManagerResize('photo', [
            'thumbnail' => [
                'width' => 250,
                'height' => 250
            ],
            'image_size' => [
                'width' => 700,
                'height' => 500
            ]
        ]);
        
    $someModel->fileManagerRotateImage('photo', 90)    
    
    $someModel->fileManagerResize('videos', $videoId, ['720p30']);
    
    $someModel->fileManagerUpdateNames('file');
    $someModel->fileManagerUpdateNames('photo');
    $someModel->fileManagerUpdateNames('videos', $videoId);
    
    $someModel->fileManagerDeleteFile('videos', $videoId);
    $someModel->fileManagerDeleteFile('file');
    
    $someModel->fileManagerSaveFiles([
        'my_videos' => [UploadedFile $video, UploadedFile $video]
    ]);
```

In order to resize videos you'll need to install HandBrake on your
system: 
https://handbrake.fr/docs/en/1.1.0/get-handbrake/download-and-install.html

```

    sudo add-apt-repository ppa:stebbins/handbrake-releases
    
    sudo apt install handbrake-gtk handbrake-cli
    
```