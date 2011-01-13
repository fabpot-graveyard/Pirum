Pirum, the simple PEAR Channel Server Manager
=============================================

Pirum is a simple and nice looking PEAR channel server manager that lets you
setup PEAR channel servers in a matter of minutes. Pirum is best suited when
you want to create small PEAR channels for a few packages written by a few
developers.

Pirum consists of just one file, a command line tool, written in PHP. There is
no external dependencies, no not need for a database, and nothing need to be
installed or configured.

More information on the official [website](http://www.pirum-project.org/).

Commands
--------

### build ###
> pirum build path/to/pear/repo

Builds the required files for the repository.

### add ###
> pirum add path/to/pear/repo package.tgz

Adds a package to the repository.

### clone-package ###
> pirum clone-package path/to/pear/repo http://path.to/get/package.tgz

Clone package will take the package from the repository and modify the channel
so that you will be able to effectively mirror the package.  This does not
take into consideration dependent packages.

This requires your php.ini to have *allow_fopen_url* on.
