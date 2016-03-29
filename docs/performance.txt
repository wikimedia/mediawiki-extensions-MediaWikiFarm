***********
Performance
***********

Method
======

Added microtime(true) at the top and bottom of the file LocalSettings.php.

On a fresh MediaWiki installation.
The only skin installed was Vector.
No extensions were installed.

The figures were obtained by loading about 15-20 times a same page, and the mean and standard deviation were estimated.
Obviously a stronger sampling protocol and computation would be better, but this only aims at giving a raw idea of the results.


Figures
=======

* Classical LocalSettings.php:
    
    mean =  2.7ms   std = 0.2ms

* LocalSettings.php with only MediaWikiFarm extension without cache:
    
    mean = 11.6ms   std = 1.0ms

* LocalSettings.php with only MediaWikiFarm extension with cache:
    
    mean =  8.1ms   std = 1.0ms
