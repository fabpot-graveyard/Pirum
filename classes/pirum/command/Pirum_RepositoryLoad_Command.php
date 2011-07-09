<?php

class Pirum_RepositoryLoad_Command
{
	/**
	 * @var Pirum_Repository
	 */
	private $repo;

	public function __construct($repo)
	{
		$this->repo = $repo;
	}

	public function build()
	{
		$this->repo->collectReleasePackageList();
	}
}

?>