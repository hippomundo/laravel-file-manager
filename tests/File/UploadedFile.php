<?php

namespace RGilyov\FileManager\Test\File;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymphonyUploadedFile;

class UploadedFile extends SymphonyUploadedFile
{
    /**
     * @param string $directory
     * @param null $name
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function move($directory, $name = null)
    {
        $target = $this->getTargetFile($directory, $name);

        if (!@copy($this->getPathname(), $target)) {
            $error = error_get_last();
            throw new FileException(
                sprintf(
                    'Could not move the file "%s" to "%s" (%s)',
                    $this->getPathname(),
                    $target,
                    strip_tags($error['message']
                    )
                )
            );
        }

        @chmod($target, 0666 & ~umask());

        return $target;
    }
}