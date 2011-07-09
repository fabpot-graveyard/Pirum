@pirum
Feature: Build
	In order to have a pirum repo
	As a maintainer
	I want to be able to build it using the executable

	@build
	Scenario: Build localhost
		Given only pirum xml file is in place
			And pirum is built
		When I issue the command `php pirum build webroot`
		Then the server index contains channel description
			And the channel is discoverable

	@add
	Scenario: Add package to localhost
		Given a built up pirum repo is in place
			And pirum is built
			And the pirum repo does not contain package
		When I issue the command `php pirum add packagename`
		Then the server index contains package description
			And the package is installable

	@remove
	Scenario: Remove package from localhost
		Given a built up pirum repo is in place
			And pirum is built
			And a package is added
			And the server index contains package description
		When I issue the command `php pirum remove packagename`
		Then the pirum repo does not contain package

	@clean
	Scenario: Remove package from localhost
		Given a built up pirum repo is in place
			And pirum is built
		When I issue the command `php pirum clean`
		Then the server index contains channel description
			And the channel is discoverable
			And the pirum repo does not contain package

