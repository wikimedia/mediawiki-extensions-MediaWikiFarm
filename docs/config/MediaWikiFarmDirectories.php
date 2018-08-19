<?php

/**
 * Configuration of MediaWikiFarm -- multiversion case.
 *
 * This file is optional. In the case standard multiversion entry points are used,
 * this can be used to customise your configuration directory, your cache directory,
 * and your MediaWiki code directory.
 *
 * It must be copied in
 *
 *   [farm]/config/MediaWikiFarmDirectories.php
 *
 * where [farm] is the MediaWikiFarm extension directory.
 */


/**
 * Configuration directory.
 *
 * Type: string.
 *
 * The value must be a readable directory for the web server.
 *
 * Depending of your openness policy, you could publish all or parts of the configuration
 * files, but probably you don’t want to publish private informations like database
 * configuration, upgrade key, etc. so be sure you distribute properly the parameters
 * across files, to easily publish some files.
 */
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';


/**
 * Code directory.
 *
 * Type: string.
 *
 * This value must be a readable directory for the web server.
 *
 * Since you want your farm can manage multiple MediaWiki versions, set this parameter to a
 * directory where each subdirectory is a MediaWiki installation in a given version+flavour.
 * Although it is probably easier to name the subdirectories following the MediaWiki version
 * inside, the names are entirely independent from the real version inside the subdirectory.
 */
$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( __FILE__ ) ) );


/**
 * Cache directory.
 *
 * Type: string|false.
 *
 * If string, this value must be a writable directory for the web server.
 *
 * This directory will be used to write cached versions of the configuration files. The cached
 * files are written in PHP, so be sure they can be executed by PHP. When the cache is used,
 * it is always checked the cached version is newer than the origin file, so that the cache
 * is always up-to-date and you don’t have to worry about differences between cache and
 * origin files.
 * In the case the origin file has a syntax error, the farm uses the cached file, so that you
 * don’t have to worry about writing syntax errors (although currently there is no mechanism
 * to alert in case of syntax error).
 * If you don’t want a cache, you can set this parameter to false.
 */
$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';


/**
 * Syslog tag.
 *
 * Type: string|false.
 *
 * If false, no logging will be issued at all. If string, syslog is used and casual log messages
 * will be issued in the facility 'USER', with the criticity level 'ERROR', and the tag defined
 * by this configuration parameter.
 */
$wgMediaWikiFarmSyslog = 'mediawikifarm';
