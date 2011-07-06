<?php

class FileSystem
{
	public function loadClasses($dir)
	{
		foreach ($this->resourceDir($dir) as $classFile) {
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

    public function mirrorDir($build, $target)
    {
        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }


        $this->removeFilesFromDir($target, $build);

        $this->copyFiles($build, $target);
    }

    protected function copyFiles($build, $target)
    {
        $fp = opendir($build);
        while (false !== $file = readdir($fp)) {
            if (in_array($file, array('.', '..')))
            {
                continue;
            }

            if (is_dir($build.'/'.$file)) {
                if (!is_dir($target.'/'.$file))
                {
                    mkdir($target.'/'.$file, 0777, true);
                }

                $this->copyFiles($build.'/'.$file, $target.'/'.$file);
            } else {
                rename($build.'/'.$file, $target.'/'.$file);
            }
        }
        closedir($fp);
    }

    protected function removeFilesFromDir($target, $build)
    {
        $fp = opendir($target);
        while (false !== $file = readdir($fp)) {
            if (in_array($file, array('.', '..')))
            {
                continue;
            }

            if (is_dir($target.'/'.$file)) {
                if (!in_array($file, array('.svn', 'CVS')))
                {
                    $this->removeFilesFromDir($target.'/'.$file, $build.'/'.$file);
                    if (!is_dir($build.'/'.$file)) {
                        rmdir($target.'/'.$file);
                    }
                }
            } else {
                unlink($target.'/'.$file);
            }
        }
        closedir($fp);
    }
}

?>
