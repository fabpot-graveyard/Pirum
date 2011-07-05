<?php

class FileSystem
{
	public static function loadClasses($dir)
	{
		$fs = new FileSystem();
		foreach ($fs->resourceDir($dir) as $classFile)
		{
			require_once $classFile;
		}
	}

	public function resourceDir($dir) {
		return new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$dir,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		);
	}

	public function deleteFile($file)
	{
		if(file_exists($file)) {
			unlink($file);
		}
	}

	public function deleteGlob($glob)
	{
		foreach (glob($glob) as $file) {
			if (is_file($file)) {
				$this->deleteFile($file);
			}
		}
	}

	public function appendTo($file, $contents)
	{
		file_put_contents($file, $contents, FILE_APPEND);
	}

	public function contentsOf($file)
	{
		return file_get_contents($file);
	}
}

?>
