***********
Performance
***********

Statistics
==========

Method
------

On a monoversion MediaWikiFarm, :code:`microtime(true)` has been added on the top and bottom of the :path:`LocalSettings.php` in the Web version (no CLI) and the difference of the two numbers is computed.

MediaWiki version is 1.27.0-alpha (Git bcafa4d), the only installed (and enabled) extension is MediaWikiFarm (Git bb677fe) and the only installed (and enabled with wfLoadSkin) skin is Vector (Git 29bde53).

Tests have been done on an Intel Core i3 (x86\_64), Linux 3.16.0-4-amd64, with PHP 7.0.4 without any cache opcache, with memcached running and configured on local host, with MySQL 5.5 Server running on local host.

The "classical LocalSettings.php" case has been done with the same configuration extracted from MediaWikiFarm and reformatted in the classic form :code:`$wgOption = 'value';`, without the extension MediaWikiFarm enabled, and with Vector loaded with :code:`wfLoadSkin`.

Composer was executed only with the 'require' section, except if indicated otherwise. Config files are all written in YAML.


Numbers
-------

All numbers are in milliseconds.

* Classical LocalSettings.php without MediaWikiFarm extension:
    
    mean =  3.0899    median =  2.8925    std =  0.6230    min =  2.4781    max =  4.4119    range =  1.9338    values = 50

* LocalSettings.php with only MediaWikiFarm extension with cache:
    
    mean =  4.8663    median =  4.7045    std =  0.7792    min =  4.0600    max =  7.0050    range =  2.9449    values = 50

* LocalSettings.php with only MediaWikiFarm extension without cache, Composer without 'require-dev' section:
    
    mean = 13.3412    median = 12.0525    std =  2.4493    min = 11.1709    max = 22.4831    range = 11.3122    values = 50

* LocalSettings.php with only MediaWikiFarm extension without cache, Composer with 'require-dev' section:
    
    mean = 14.7820    median = 13.8605    std =  2.0367    min = 13.0639    max = 20.6609    range =  7.5970    values = 50

