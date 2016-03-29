*******
Scripts
*******

Here are detailled the scripts provided with MediaWikiFarm:
* :command:`mwscript` to execute a MediaWiki maintenance script in the context of a farm
* :command:`validate-schema` to validate the main config file

mwscript
========

:command:`mwscript` must be prepended to traditionnal maintenance scripts. For instance, to run :command:`maintenace/update.php`, you can run one of the two equivalent commands:
* :command:`php mwscript.php maintenance/update.php --wiki=mywiki.example.org`
* :command:`php mwscript.php update --wiki=mywiki.example.org`
If the command doesn’t begin with "maintenance/" and doesn’t end with ".php", these are added.

For your convenience, you can add it as an alias in your ~/.bashrc. The exact command is given when you run :command:`php mwscript.php --help`. With this alias, the command becomes :command:`mwscript update --wiki=mywiki.example.org`.


