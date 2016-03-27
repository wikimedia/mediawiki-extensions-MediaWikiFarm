<?php
/**
 * Entry point for CLI scripts in the context of a monoversion or multiversion MediaWiki farm.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Protect against web entry
if( PHP_SAPI != 'cli' ) exit;


# Configuration of the MediaWiki Farm
# The config file is in different location depending if it is a mono- or multi-version installation
if( is_file( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/includes/DefaultSettings.php' ) ) {
	
	$IP = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
	require "$IP/LocalSettings.php";
}
else {
	
	$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( __FILE__ ) ) );
	$wgMediaWikiFarmConfigDir = '/etc/mediawiki';
	$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';
	require_once dirname( dirname( __FILE__ ) ) . '/config/MediaWikiFarmDirectories.php';
}


# Include library
// @codingStandardsIgnoreStart MediaWiki.Usage.DirUsage.FunctionFound
require_once dirname( dirname( __FILE__ ) ) . '/src/MediaWikiFarm.php';
// @codingStandardsIgnoreEnd


/**
 * Get a command line parameter.
 * 
 * The parameter can be removed from the list, except the first parameter (script name).
 * 
 * @param string|integer $name Parameter name or position (from 0).
 * @param bool $shift Remove this parameter from the list?
 * @return string|null Value of the parameter.
 */
function mwfGetParam( $name, $shift = true ) {
	
	global $argc, $argv;
	
	$posArg = 0;
	$nbArgs = 0;
	$value = null;
	
	# Search a named parameter
	if( is_string( $name ) ) {
		
		for( $posArg = 1; $posArg < $argc; $posArg++ ) {
				
			if( substr( $argv[$posArg], 0, strlen($name)+3 ) == '--'.$name.'=' ) {
				$value = substr( $argv[$posArg], strlen($name)+3 );
				$nbArgs = 1;
				break;
			}
			elseif( $argv[$posArg] == '--'.$name && $posArg < $argc - 1 ) {
				$value = $argv[$posArg+1];
				$nbArgs = 2;
				break;
			}
		}
	}
	
	# Search a positional parameter
	elseif( is_int( $name ) ) {
		if( $name == 0 )
			$shift = false;
		if( $name >= $argc )
			return null;
		$value = $argv[$name];
		$nbArgs = 1;
	}
	
	# Remove the parameter from the list
	if( $shift ) {
		
		$argc -= $nbArgs;
		$argv = array_merge( array_slice( $argv, 0, $posArg ), array_slice( $argv, $posArg+$nbArgs ) );
	}
	
	return $value;
}

/**
 * Display help and return success or error.
 * 
 * @param bool $error Return an error code?
 * @return void
 */
function mwfUsage( $error = true ) {
	
	global $argv;
	$fullPath = realpath( $argv[0] );
	
	# Minimal help, be it an error or not
	echo <<<HELP

    Usage: php {$argv[0]} MediaWikiScript --wiki=hostname …

    Parameters:

      - MediaWikiScript: name of the script, e.g. "maintenance/runJobs.php"
      - hostname: hostname of the wiki, e.g. "mywiki.example.org"


HELP;
	
	# Regular help
	if( !$error ) echo <<<HELP
    | Note simple names as "runJobs" will be converted to "maintenance/runJobs.php".
    |
    | For easier use, you can alias it in your shell:
    |
    |     alias mwscript='php $fullPath'


HELP;
	
	exit( $error ? 1 : 0 );
}


# Return usage
if( $argc == 2 && ($argv[1] == '-h' || $argv[1] == '--help') ) mwfUsage( false );

# Get wiki
$mwfHost = mwfGetParam( 'wiki' );
if( is_null( $mwfHost ) ) mwfUsage();

# Get script
$mwfScript = mwfGetParam( 1 );
if( is_null( $mwfScript ) ) mwfUsage();
if( preg_match( '/^[a-zA-Z-]+$/', $mwfScript ) )
	$mwfScript = 'maintenance/' . $mwfScript . '.php';


# Initialise the requested version
MediaWikiFarm::load( $mwfScript, $mwfHost );

# Display parameters
$mwfVersion = MediaWikiFarm::getInstance()->params['version'] ? MediaWikiFarm::getInstance()->params['version'] : 'current';
echo <<<PARAMS

Wiki:    $mwfHost (wikiID: {$wgMediaWikiFarm->params['wikiID']}; suffix: {$wgMediaWikiFarm->params['suffix']})
Version: $mwfVersion: {$wgMediaWikiFarm->params['code']}
Script:  $mwfScript


PARAMS;

# Clean this script
if( !is_file( $mwfScript ) ) {
	echo "Script not found.\n";
	exit( 1 );
}
$argv[0] = $mwfScript;
unset( $mwfHost );
unset( $mwfScript );
unset( $mwfVersion );
unset( $IP );


# Execute the script
// Possibly it could be better to do a true system call with a child process (PHP function "system"), BUT
// hostname must be passed as an environment variable and more importantly, in the current implementation of
// MediaWikiFarm, the called version of the extension will be $version/extensions/MediaWikiFarm, and this
// version is probably not configured as a standalone extension (directories set in LocalSettings.php); so
// it will not work in current implementation.
require $argv[0];

