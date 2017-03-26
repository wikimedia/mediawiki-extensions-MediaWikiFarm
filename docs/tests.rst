*****
Tests
*****

This page is about the testing infrastructure.


PHP 5.2
=======

It was tested with PHP 5.2 and PHPUnit 3.4 as of March 26, 2017 and was reported as working (with some fixes then).

Here is the process to test with these old softwares:
1. Compile PHP 5.2
2. Clone with Git PHPUnit and select the tag 3.4
3. Copy phpunit.php and the directory PHPUnit inside [MediaWikiFarm]/tests/phpunit
4. In the file [MediaWikiFarm]/tests/phpunit/MediaWikiFarmTestCase.php:
   1. Comment " class MediaWikiTestCase extends PHPUnit\Framework\TestCase {} " (PHP 5.2 does not know namespaces)
   2. Replace the class inheritance of MediaWikiTestCase from PHPUnit_Framework_TestCase to PHPUnit_Extensions_OutputTestCase
   3. On the top of the file, add " require_once 'PHPUnit/Extensions/OutputTestCase.php'; "
5. cd [MediaWikiFarm]/tests/phpunit; for file in \`ls \*Test.php\`; do php5.2 phpunit.php $file; done;
