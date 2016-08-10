************
Installation
************

This explains how to install MediaWikiFarm, and how to transition from monoversion to multiversion mode. Only the system configuration is explained here, the day-to-day configuration of MediaWikiFarm and MediaWiki instances will be detailled in the next chapter.

The two modes (monoversion and multiversion) are strictly exclusive; any mix could produce errors, and even data corruption could happen (e.g. if you run :command:`maintenance/update.php` on a wrong MediaWiki version). However, a transition path between monoversion and multiversion mode has been created and should be safe.

Original installations could be either in monoversion mode or multiversion mode, there is no need to begin in monoversion mode.

There shouldn’t be any interruption of service if everything is followed carefully.

Preparation
===========

It is out of scope to explain out to install and configure the full HTTP stack, neither how to make it compatible with a MediaWiki farm and evolve with the time. For a raw overview, you must configure in a multisite fashion: the DNS servers, the HTTP servers, and possibly the associated security versions DNSSEC and HTTPS, and possibly the domain names depending of your specific configuration. It is also out of scope the installation and configuration of other services and backends: database servers, memcached servers, other caching or performance services, MediaWiki external services as Parsoid, Mathoid, Citoid, etc. You should also be comfortable with command line on a \*nix system.

It is assumed you have an existing MediaWiki (standalone) installation. A new installation is theoretically possible, but it has not been tested.

Before installing the files, you must prepare these informations:
* regular expression(s) for your farm(s) with named patterns;
* *configuration directory* where will be placed MediaWiki configurations, the default is :path:`/etc/mediawiki`;
* *cache directory*, a directory where config files will be cached; the default is :path:`/tmp/mw-cache`; it can speed up MediaWikiFarm from 9ms to 2ms (without OpCache).

For your initial configuration, you can choose the regular expression of your farm simply as the name of your existing wiki, e.g. "mywiki\.example\.org". If you do that, the suffix and wikiID can be fixed without variables, but you should quickly think how you want to organise your wikis and farms to change these to significant values before you have too much things in your config files.

Download the MediaWikiFarm extension, you will need some files and scripts from it.

Config files can be read in YAML, JSON, or PHP syntax. The two later have native PHP functions, but the former need an external library. So if you want to use the YAML syntax, it is entirely possible but it creates a dependency (it is the only dependency).

Create the configuration directory, make it readable for PHP, and copy in this directory the sample file :path:`docs/config/farms.yml` from MediaWikiFarm (or any other format if you prefer). In the placeholder section, replace with your farm regex, and replace the suffix and wikiID keys with the variables coming from your regex. You will note the only config file is :path:`LocalSettings.php`, it will be your existing config file (see below).

Now jump to monoversion or multiversion mode.

Monoversion mode
================

In this mode, MediaWikiFarm can be installed (almost) like any other MediaWiki extension, but the configuration is different.

1. Copy the extension MediaWikiFarm and install it in the subdirectory :path:`$IP/extensions/MediaWikiFarm`;
2. If you downloaded it from Git and want the YAML syntax, go inside the directory and run :command:`composer install --no-dev` (see Composer_ if needed);
3. Copy your existing :path:`LocalSettings.php` file in your configuration directory;
4. Verify there is no absolute path inside: ``__FILE__`` and ``__DIR__`` should probably be replaced by their original value to avoid any missing file require (it’s fine to use the MediaWiki installation variable :path:`$IP`);
5. Copy the MediaWikiFarm file :path:`docs/config/LocalSettings.php` next to your existing :path:`$IP/LocalSettings.php`, e.g. in :path:`$IP/LocalSettings.new.php`;
6. Check or customise the directory paths inside;
7. /!\ Make MediaWikiFarm live by moving this file in place of your existing :path:`$IP/LocalSettings.php`.

Multiversion mode
=================

Decide on the path where will be MediaWiki versions, this will be called the *code directory*. It is recalled each MediaWiki version (version + flavour more exactly) will be in a subdirectory of this code directory, and the names of these subdirectories will be the names of the versions. This code directory is independent from the configuration directory.

It is assumed here all the directories are not used on the live website; if it is not the case, you must be more careful.

1. Create this directory, copy your existing MediaWiki installation in a subdirectory, and rename this subdirectory to an understandable name, for instance the name of the MediaWiki version (e.g. "1.25.5").
2. Copy the extension MediaWikiFarm in a subdirectory of this code directory. You can name this subdirectory as you want.
3. Go inside this directory; if there is no Composer :path:`vendor` directory and you want the YAML syntax, run :command:`composer install --no-dev` (see Composer_ if needed);
4. Always in the MediaWikiFarm directory, create a directory :path:`config`, copy inside the file :path:`docs/config/MediaWikiFarmDirectories.php`, and check or customise the directory paths inside.
5. Go inside your MediaWiki installation and copy your existing :path:`LocalSettings.php` file in your configuration directory.
6. Verify there is no absolute path inside: ``__FILE__`` and ``__DIR__`` should probably be replaced by their corresponding value in the MediaWiki installation directory to avoid any missing file require (it’s fine to use the MediaWiki installation variable :path:`$IP`).
7. Go inside your MediaWiki installation and replace the existing :path:`$IP/LocalSettings.php` by the MediaWikiFarm file :path:`docs/config/LocalSettings.multiversion.php` (and renaming it with the classical name :path:`$IP/LocalSettings.php`).
8. /!\ Make MediaWikiFarm live by changing your Web server configuration to make entry points index.php and others point to the files :path:`www/index.php` and others in the MediaWikiFarm directory.

Transition from monoversion to multiversion mode
================================================

1. Be sure you have the last version of the extension MediaWikiFarm; if not, update.
2. Follow the instructions to create a multiversion installation, just don’t do the last step.
3. Convert all your files containing the lists of existing wikis from lists to dictionaries, the key being the wikiID and the value the version (name of the subdirectory). In YAML syntax, each line was (e.g.) :code:`- wikiname` and becomes :code:`wikiname: 1.25.5`.
4. /!\ Make multiversion MediaWikiFarm live by changing your Web server configuration to make entry points index.php and others point to the files :path:`www/index.php` and others in the MediaWikiFarm directory.
5. In the MediaWiki installation, replace the monoversion MediaWikiFarm :path:`LocalSettings.php` files to multiversion MediaWikiFarm :path:`LocalSettings.php`: these files can be found in the MediaWikiFarm directory :path:`docs/config/LocalSettings.multiversion.php` (and rename it with the classical name :path:`$IP/LocalSettings.php`).
6. Remove the MediaWikiFarm extension from the classical directory :path:`$IP/extensions/MediaWikiFarm`.

For information, transition from multiversion to monoversion mode is simply the contrary of the steps 6, 5, 4, 3, but care must be done because MediaWiki could not like change from one version to another without a clean transition path.

.. _Composer: https://www.getcomposer.org

