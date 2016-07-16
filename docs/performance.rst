***********
Performance
***********

Statistics
==========

Method
------

On a monoversion MediaWikiFarm, :code:`microtime(true)` has been added on the top and bottom of the :path:`LocalSettings.php` in the Web version (no CLI) and the difference of the two numbers is computed.

MediaWiki version is 1.27.0-alpha (Git bcafa4d), the only installed (and enabled) extension is MediaWikiFarm (Git bb677fe) and the only installed (and enabled with wfLoadSkin) skin is Vector (Git 29bde53).

Tests have been done on an Intel Core i3 (x86\_64), Linux 3.16.0-4-amd64, with PHP 7.0.4 without any cache opcache (except if indicated otherwise), with memcached running and configured on local host, with MySQL 5.5 Server running on local host.

The "classical LocalSettings.php" case has been done with the same configuration extracted from MediaWikiFarm and reformatted in the classic form :code:`$wgOption = 'value';`, without the extension MediaWikiFarm enabled, and with Vector loaded with :code:`wfLoadSkin`.

Composer was executed only with the 'require' section, except if indicated otherwise. Config files are all written in YAML.

To give an order of magnitude of the full request, the time indicated by MediaWiki in the source code of the page (wgBackendResponseTime) is around 320-370 ms without OPcache and 40-50 ms with OPcache.


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

* Classical LocalSettings.php without MediaWikiFarm extension, with PHP extension OPcache:
    
    mean =  0.1879    median =  0.1781    std =  0.0820    min =  0.1180    max =  0.4871    range =  0.3691    values = 50

* LocalSettings.php with only MediaWikiFarm extension with cache, with PHP extension OPcache:
    
    mean =  0.7494    median =  0.6160    std =  0.2886    min =  0.4861    max =  1.6530    range =  1.1668    values = 50

* LocalSettings.php with only MediaWikiFarm extension with cache, with PHP extension OPcache and cache in PHP format:
    
    mean =  0.3264    median =  0.2786    std =  0.1485    min =  0.1972    max =  0.8500    range =  0.6528    values = 50


Performance architecture
========================

Since the early versions of MediaWikiFarm, only the configuration of each wiki was cached (this was inspired from the Wikimedia’s CommonSettings.php). The only invalidation of this cache is when origin files are changed.

This was a good point to improve performance, but two bottlenecks were then identified:
* the reading and parsing of the YAML files (main config file, existence of variables, config files) was quite slow (7-8 ms);
* YAML files needed an autoloading from Composer, and this was quite slow (2-3 ms).

To improve these two points, a general cache directory is defined, and if this one exists, the reading function systematically write a cached file in a format natively understood by PHP -- serialised format. The next trivial step was to call Composer autoloader only when a YAML file is about to be read. After this operation, the mean time spent in LocalSettings.php decreased from about 13-15 ms to 4-5 ms. Hence, without OPcache, given the time spent by a classical LocalSettings.php is 3 ms, MediaWikiFarm costs 2 ms (over a total of 350 ms). With OPcache, MediaWikiFarm costs 0.56 ms (over a total of 45 ms).

It was tried the CDB format as an alternative to serialised format, but I was not convinced by the performance when I tried to load the entire file (wiki configuration). Three limitations of the CDB format are: it is a PHP extension (a Composer fallback library was written by Wikimedia), it is a dictionary (and cannot be a list), and values are strings (a serialisation of the value must be applied beforehand). Possibly the gain is more important when only some informations are read, as it could be the case for existence files. Another test in this direction could be tried.

Another development direction is to create a unique cache file containing all wikiIDs of a given farm and associated versions and invalidate this one each time an origin file is changed. Possibly the CDB format could give good performance in this case. In this scenario, with the CDB format, the 'existence' part of MediaWikiFarm would be time-constant with a hopefully small time.
