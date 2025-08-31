<?php

namespace Arembi\Xfw\Core;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;


abstract class FS {

    private static $filesystems;
    private static $activeFilesystem;


    public static function addLocalFilesystem(string $name, string $rootDirPath, ?LocalFilesystemAdapter $adapter = null)
	{
		if (empty(self::$filesystems[$name])) {
           
            $adapter = $adapter ?? new LocalFilesystemAdapter(
                $rootDirPath,
                PortableVisibilityConverter::fromArray([
                    'file' => [
                        'public' => 0640,
                        'private' => 0604,
                    ],
                    'dir' => [
                        'public' => 0740,
                        'private' => 7604,
                    ],
                ]),
                LOCK_EX,
                LocalFilesystemAdapter::DISALLOW_LINKS
            );

            self::$filesystems[$name] = new Filesystem($adapter);
            
            return self::$filesystems[$name];
        } else {
            return null;
        }
	}


    public static function getFilesystem(string $fs)
    {
        return self::$filesystems[$fs] ?? null;
    }


    public static function activeFilesystem(?string $fs = null)
    {
        if ($fs === null) {
            return self::$activeFilesystem;
        } else {
            self::$activeFilesystem = $fs;
        }
    }


    public static function write(string $path, $contents, array $config = [], ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        $filesystem->write($path, $contents, $config);
    }


    public static function writeStream(string $path, $contents, array $config = [], ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        $filesystem->writeStream($path, $contents, $config);
    }


    public static function read(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->read($path);
    }


    public static function readStream(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->readStream($path);
    }


    public static function delete(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        $filesystem->delete($path);
    }


    public static function deleteDirectory(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        $filesystem->deleteDirectory($path);
    }


    public static function listContents(string $path, bool $recursive = false, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->listContents($path, $recursive);
    }


    public static function fileExists(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->fileExists($path);
    }


    public static function directoryExists(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->directoryExistst($path);
    }


    public static function has(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->has($path);
    }


    public static function lastModified(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->lastModified($path);
    }


    public static function mimeType(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->mimeType($path);
    }


    public static function fileSize(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->fileSize($path);
    }


    public static function visibility(string $path, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->visibility($path);
    }


    public static function createDirectory(string $path, array $config, ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->createDirectory($path, $config);
    }


    public static function move(string $source, string $destination, array $config = [], ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->move($source, $destination, $config);
    }


    public static function copy(string $source, string $destination, array $config = [], ?string $fs = null)
    {
        $filesystem = $fs === null ? self::$filesystems[self::$activeFilesystem] : self::$filesystems[$fs];
        return $filesystem->copy($source, $destination, $config);
    }
    
}