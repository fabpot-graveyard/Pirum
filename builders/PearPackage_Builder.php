<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fqqdk
 * Date: 7/4/11
 * Time: 3:37 PM
 * To change this template use File | Settings | File Templates.
 */
 
class PearPackage_Builder
{
    public function build()
    {
        exec('pear package');
    }
}
