@buildscript
Feature: Build
	In order to maintain pirum
	As a developer
	I want to be able to build and test using a simple build script

	@build
	Scenario: Build pirum
		When I issue the command `php build.php build`
		Then the following files will exist
			|File           |
			|pirum          |
			|package.xml    |
			|Pirum-1.0.1.tgz|

	@clean
	Scenario: Clean pirum
		When I issue the command `php build.php clean`
		Then the following files will not exist
			|File           |
			|pirum          |
			|package.xml    |
			|Pirum-1.0.1.tgz|

