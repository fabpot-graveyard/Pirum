<?php

interface Resource
{
	public function delete();
	public function mergeTo($targetFile);
	public function getContents();
}

?>