*********
Internals
*********

Overview
========

Most of the code is inside the class MediaWikiFarm, except the code used to load the global configuration parameters inside the global scope.

The class MediaWikiFarm is in charge of loading the farm configuration, managing the configuration cache, selectionning the requested farm and wiki, compiling the configuration for a given wiki. The executable file :path:`src/main.php` is the strict equivalent of LocalSettings.php: it loads the configuration parameters inside the global scope.

There are a lot of various configurations, depending on the mono- or multi-version installation, on the PHP version, on the MediaWiki version, and on the PHP SAPI used (Web or CLI). Various flags and :code:`require_once` are used to select the right files and functions to load the configuration. The main difficulty is to correctly handle the CLI case, mainly because MediaWiki is mostly designed to have only one wiki per installation.

Testing
=======

MediaWikiFarm has an experimental support of unit tests with `PHPUnit`_.

Because of the very specific use case of PHPUnit called in CLI, it is recommended to set up a classical wiki installation with a classical LocalSettings.php with its own database and (fakely) load MediaWikiFarm with :code:`wfLoadExtension( 'MediaWikiFarm' )`. In fact this will not load the MediaWikiFarm executable code due to a lack of of support of extension registration (difficult problem in this case), so this will only load the class MediaWikiFarm needed to execute unit tests.

Then, to execute PHPUnit, go to root MediaWiki directory and run :command:`php tests/phpunit/phpunit.php --group MediaWikiFarm`. You can add :command:`--debug` if you want more details.

.. _PHPUnit: http://www.phpunit.de

