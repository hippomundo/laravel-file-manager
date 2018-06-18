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
    */

    'media' => [

        'default' => [
            "directory" => "media",
            "max_size"  => 500,
            "thumbnail" => ["width" => 250, "height" => 250]
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Video configurations
    |--------------------------------------------------------------------------
    |
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
    */

    'files' => [

        'default' => [
            "directory" => "files",
        ]

    ],
];
