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

	public function isDir($dir)
	{
		return is_dir($dir);
	}

	public function fileExists($file)
	{
		return file_exists($file);
	}

	public function mkDir($dir)
	{
        if ($this->isDir($dir)) {
			return;
        }

		mkdir($dir, 0777, true);
	}

	public function getTempDir($seed)
	{
		return sys_get_temp_dir().'/'.$seed.'_'.uniqid();
	}

	public function createTempDir($seed, $path = '')
	{
		$result = sys_get_temp_dir().'/'.$seed.'_'.uniqid();
		$this->mkDir($result.$path);
		var_dump($result.$path);
		return $result;
	}

    public function removeDir($target)
    {
        $fp = opendir($target);
        while (false !== $file = readdir($fp)) {
            if (in_array($file, array('.', '..')))
            {
                continue;
            }

            if (is_dir($target.'/'.$file)) {
                $this->removeDir($target.'/'.$file);
            } else {
                unlink($target.'/'.$file);
            }
        }
        closedir($fp);
        rmdir($target);
    }
}

?>
