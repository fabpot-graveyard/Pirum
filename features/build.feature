Feature: Build
  In order to have a pirum repo
  As a maintainer
  I want to be able to build it using the executable

  Scenario: Build localhost
    Given there is a directory '/var/www/pear'
		And the pirum build files are cleaned
		And the pirum standalone is built
	When I issue the command `php pirum build '/var/www/pear'`
	Then the exit status of the command should be 0
		And the following files should exist in '/var/www/pear':
			| file                            |
			| channel.xml                     |
			| feed.xml                        |
			| index.html                      |
			| pirum.css                       |
			| pirum.php                       |
			| pirum.xml                       |
			| rest/c/categories.xml           |
			| rest/c/Default/info.xml         |
			| rest/c/Default/packagesinfo.xml |
			| rest/c/Default/packages.xml     |
			| rest/m/allmaintainers.xml       |
			| rest/p/packages.xml             |

  