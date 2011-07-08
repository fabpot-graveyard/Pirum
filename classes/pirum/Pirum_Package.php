<?php

class Pirum_Package extends SimpleXMLElement
{
	public function validate($name, $version)
	{
        if ($name != (string) $this->name) {
            throw new InvalidArgumentException(sprintf(
				'The package.xml name "%s" does not match the name of the archive file "%s".',
				$this->name, $name)
			);
        }

        // check version
        if ($version != (string) $this->version->release) {
            throw new InvalidArgumentException(sprintf(
				'The package.xml version "%s" does not match the version of the archive file "%s".',
				$this->version->release, $version));
        }
	}
}

?>