Feature: Build
  In order to have a pirum repo
  As a maintainer
  I want to be able to build it using the executable

  Scenario: Build localhost
    Given there is a directory '/var/www'
		And the pirum build files are cleaned
		And the pirum standalone is built
	When I issue the command `php pirum build '/var/www'`
	Then the exit status of the command should be 0
		And pirum files should exist
  