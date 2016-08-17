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

MediaWikiFarm is unit tested with `PHPUnit`_. The unit tests check (only for now) the first part of the extension: construction of the object and existence of a given wiki. This is tested in the two installation modes (monoversion and multiversion). The tests successfully run when executed in MediaWiki 1.19 to 1.27 and 1.28alpha, with PHP 5.6 and PHP 7.0.

Executing PHPUnit is like executing any other script in the farm: :command:`php extensions/MediaWikiFarm/scripts/mwscript.php --wiki=your.wiki.example.org tests/phpunit/phpunit.php --group MediaWikiFarm`. You can add :command:`-v` or even :command:`--debug` if you want more details.

The command varies depending on the MediaWiki version tested: (Possibly the first form can be extended to previous versions up to MW 1.17 since the hook UnitTestsList was introduced there, but I didn’t find how to.)

MediaWiki 1.27-1.28alpha:
* :command:`php mwscript.php --wiki=mywiki.example.org tests/phpunit/phpunit.php --group MediaWikiFarm`

MediaWiki 1.24-1.26:
* :command:`php mwscript.php --wiki=mywiki.example.org tests/phpunit/phpunit.php [farmdir]/tests/phpunit`

MediaWiki 1.19-1.23:
* Install PHPUnit 3.7.38 somewhere, e.g. download with Git and execute Composer inside
* :command:`php mwscript.php --wiki=mywiki.example.org tests/phpunit/phpunit.php --with-phpunitdir [phpunitdir] [farmdir]/tests/phpunit`

Note that there are two (one by class) hidden tests automatically added by MediaWiki 1.21 and greater (testMediaWikiTestCaseParentSetupCalled) checking that the setUp function calls its parent. Hence it is normal to see a different number of tests between 1.20 and 1.21.

.. _PHPUnit: http://www.phpunit.de

