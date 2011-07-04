<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fqqdk
 * Date: 7/4/11
 * Time: 3:34 PM
 * To change this template use File | Settings | File Templates.
 */

class TargetFile extends SplFileInfo
{
    public function append($content)
    {
        file_put_contents($this->getPathname(), $content, FILE_APPEND);
    }
    public function clean()
    {
        if (!file_exists($this->getPathname()))
            return;

        unlink($this->getPathname());
    }
}
