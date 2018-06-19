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
            "directory"                   => "media",
            "image_size"                  => 500,
            "thumbnail"                   => ["width" => 250, "height" => 250],
            "update_file_names_on_change" => true
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
