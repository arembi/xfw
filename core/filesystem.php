<?php

namespace Arembi\Xfw\Core;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;



abstract class FS {

	private static $filesystems;
	private static $activeFilesystem;

	public static function addLocalFilesystem(string $name, string $rootDir, ?array $visibility = null): Filesystem|false
	{
		if (!empty(self::$filesystems[$name])) {
			return false;
		}

		$visibility ??= [
			'file' => [
				'public' => 0644,
				'private' => 0600,
			],
			'dir' => [
				'public' => 0755,
				'private' => 0700,
			],
		];

		$adapter = new LocalFilesystemAdapter(
			$rootDir,
			PortableVisibilityConverter::fromArray($visibility),
			LOCK_EX,
			LocalFilesystemAdapter::DISALLOW_LINKS
		);

		self::$filesystems[$name] = new Filesystem($adapter);
		
		return self::$filesystems[$name];
	}


	public static function zipArchive(string $path, ?array $visibility = null)
	{
		$filesystemName = 'zip_';
		$index = 0;

		$visibility ??= [
			'file' => [
				'public' => 0644,
				'private' => 0600,
			],
			'dir' => [
				'public' => 0755,
				'private' => 0700,
			],
		];

		while (!empty(self::$filesystems[$filesystemName . $index])) {
			$index++;
		}
		
		$adapter = new ZipArchiveAdapter(
			new FilesystemZipArchiveProvider($path)
		);

		self::$filesystems[$filesystemName . $index] = new Filesystem($adapter);
		
		return self::$filesystems[$filesystemName . $index];
	}


	public static function getFilesystem(string $fs): Filesystem|false
	{
		return self::$filesystems[$fs] ?? null;
	}


	public static function activeFilesystem(?string $fs = null): string|null
	{
		if ($fs !== null) {
			self::$activeFilesystem = $fs;
		}
		return self::$activeFilesystem;
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