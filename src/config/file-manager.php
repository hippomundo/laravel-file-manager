<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Common folder for all files
    |--------------------------------------------------------------------------
    */

    'folder' => 'files',

    /*
    |--------------------------------------------------------------------------
    | Backup files into another disk if set
    |--------------------------------------------------------------------------
    |
    | You may specify a disk that can be used to backup all uploaded files here.
    | It might be useful when a cloud being used on the system, so if the cloud
    | will not work because of any reasons you may still load files from, for
    | example, `local` disk.
    |
    */

    'backup_disk' => null,

    /*
    |--------------------------------------------------------------------------
    | Serve files from backup disk
    |--------------------------------------------------------------------------
    |
    | If `true`, all files paths will have links to backup disk
    |
    */

    'serve_files_from_backup_disk' => false,

    /*
    |--------------------------------------------------------------------------
    | Media configurations
    |--------------------------------------------------------------------------
    |
    | Directory is the folder that goes after main 'folder', 'files/{directory}'.
    | Image size is the size of big image (has no impact on original image).
    | Thumbnail size is size of thumbnail.
    | Update file names on change (rotate, resize) in order to reset browser's
    | cache. We would need to rename thumbnail and image so we will see image
    | changes immediately.
    |
    */

    'media' => [

        'default' => [
            "directory"              => "media",
            "image_size"             => 500,
            "thumbnail"              => ["width" => 250, "height" => 250],
            "update_names_on_change" => true
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Video configurations
    |--------------------------------------------------------------------------
    |
    | Directory is the folder that goes after main 'folder', 'files/{directory}'.
    | Resize will work only if the HandBrake package installed on your server:
    | https://handbrake.fr/docs/en/1.1.0/get-handbrake/download-and-install.html
    |
    | Possible resize values:
    |   - 2160p60
    |   - 1080p30
    |   - 720p30
    |   - 576p25
    |   - 480p30
    |
    */

    'video' => [

        'default' => [
            "directory" => "video",
            "resize"    => "576p25"
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Files configurations
    |--------------------------------------------------------------------------
    |
    | Directory is the folder that goes after main 'folder', 'files/{directory}'.
    |
    */

    'files' => [

        'default' => [
            "directory" => "files",
        ]

    ],
];
