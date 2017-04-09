*******
Scripts
*******

Here are detailled the scripts provided with MediaWikiFarm:
* :command:`mwscript` to execute a MediaWiki maintenance script in the context of a farm
* :command:`mwcomposer` to install Composer dependencies and give the opportunity to activate per-wiki a Composer-managed MediaWiki extension
* :command:`validate-schema` to validate the main config file

mwscript
========

:command:`mwscript` must be prepended to traditionnal maintenance scripts. For instance, to run :command:`maintenace/update.php`, you can run one of the two equivalent commands:
* :command:`php mwscript.php maintenance/update.php --wiki=mywiki.example.org`
* :command:`php mwscript.php update --wiki=mywiki.example.org`
If the command doesn’t begin with "maintenance/" and doesn’t end with ".php", these are added.

For your convenience, you can add it as an alias in your ~/.bashrc. The exact command is given when you run :command:`php mwscript.php --help`. With this alias, the command becomes :command:`mwscript update --wiki=mywiki.example.org`.

mwcomposer
==========

:command:`mwcomposer` is very similar to :command:`composer` (and this one must be installed and is called) but creates a modified `vendor` directory (with multiple Composer autoloaders, a modified file `autoload.php`, and a added file `MediaWikiExtensions.php`) to activate per-wiki Composer-managed MediaWiki extensions. If you directly run :command:`composer` instead of :command:`mwcomposer` it will also "work", but all Composer-managed MediaWiki extensions will be activated on all wikis using this MediaWiki version (possibly some extensions have custom mechanisms to prevent themselves from being activated without specific parameters).

To run this script, you must add in your `composer.json` or `composer.local.json` your Composer-managed MediaWiki extensions (and skins), then run this script in the MediaWiki directory just like you would have run Composer.

Internally this script:
* run Composer with the original composer.json, so that if there are incompatibilities between some Composer-managed MediaWiki extensions, you are warned;
* run Composer one time per extension (so N Composer runs) by crafting the composer.json as if only the extension was the only one activated extension;
* run Composer without any extension;
* install MediaWiki extensions and skins in their standard directories;
* create a `vendor` directory with all Composer libraries, N Composer autoloader per MediaWiki extension, 1 Composer autoloader without any MediaWiki extension, a specific autoloader in place of `autoload.php`, and one `MediaWikiExtensions.php`.
Note that each Composer run is done in a temporary directory to avoid issues with the real MediaWiki directory. At runtime, the `autoload.php` file requests MediaWikiFarm to known what Composer-managed MediaWiki extensions are enabled and loads their autoloaders. The file `MediaWikiExtensions.php` is used by MediaWikiFarm during configuration compilation (to create the `LocalSettings.php` files) to know the dependency graph between Composer-managed MediaWiki extensions (given Composer computes and respects the dependencies, MediaWikiFarm should be aware of these dependencies).
