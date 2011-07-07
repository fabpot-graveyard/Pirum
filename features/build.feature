Feature: Build
	In order to have a pirum repo
	As a maintainer
	I want to be able to build it using the executable

	Scenario: Build localhost
		Given pirum xml file is in place
			And the pirum standalone is built
		When I issue the command `php pirum build webroot`
		Then the server index contains channel description
			And the channel is discoverable

	Scenario: Add package to localhost
		Given a built up pirum repo is in place
			And the pirum repo does not contain package
		When I issue the command `php pirum add packagename`
		Then the server index contains package description
			And the package is installable

	Scenario: Remove package from localhost
		Given a built up pirum repo is in place
			And the server index contains package description
		When I issue the command `php pirum remove packagename`
		Then the pirum repo does not contain package

	Scenario: Remove package from localhost
		Given a built up pirum repo is in place
		When I issue the command `php pirum clean`
		Then the pirum repo only contains pirum.xml

