@pirum
Feature: Build
	In order to have a pirum repo
	As a maintainer
	I want to be able to build it using the executable

	@build
	Scenario: Build localhost
		Given pirum is built
			And only pirum xml file is in place
		When I issue the command `php pirum build webroot`
		Then the server index contains channel description
			And the channel is discoverable

	@add
	Scenario: Add package to localhost
		Given pirum is built
			And a built up pirum repo is in place
			And the pirum repo does not contain package
		When I issue the command `php pirum add packagename`
		Then the server index contains package description
			And the package is installable
			And the following xml files should exist:
			| File                            |Content        |
			| channel.xml                     |Dummy          |
			| feed.xml                        |Dummy          |
			| rest/c/categories.xml           |Default        |
			| rest/c/Default/info.xml         |Default        |
			| rest/c/Default/packagesinfo.xml |Dummy          |
			| rest/c/Default/packages.xml     |Dummy          |
			| rest/m/allmaintainers.xml       |Dummy|
			| rest/p/packages.xml             |Dummy          |

	@remove
	Scenario: Remove package from localhost
		Given pirum is built
			And a built up pirum repo is in place
			And a package is added
			And the server index contains package description
		When I issue the command `php pirum remove packagename`
		Then the pirum repo does not contain package

	@clean
	Scenario: Remove package from localhost
		Given pirum is built
			And a built up pirum repo is in place
		When I issue the command `php pirum clean`
		Then the server index contains channel description
			And the channel is discoverable
			And the pirum repo does not contain package

