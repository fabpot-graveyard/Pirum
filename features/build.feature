Feature: Build
	In order to have a pirum repo
	As a maintainer
	I want to be able to build it using the executable

	Scenario: Build localhost
		Given pirum xml file is in place
			And the pirum build files are cleaned
			And the pirum standalone is built
		When I issue the command `php pirum build webroot`
		Then the server index contains channel description
			And the channel is discoverable

  