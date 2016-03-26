<?php
/**
 * Class MediaWikiFarm.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) && !defined( 'MEDIAWIKI_FARM' ) ) exit;

# Protect against double inclusion
# This could happen even with require_once in the case of multiversion installation
if( defined( 'MEDIAWIKI_FARM' ) ) return;

/**
 * This class computes the configuration of a specific wiki from a set of configuration files.
 * The configuration is composed of the list of authorised wikis and different configuration
 * files, possibly with different permissions. All files are written in YAML syntax.
 */
class MediaWikiFarm {
	
	/* 
	 * Properties
	 * ---------- */
	
	/** @var MediaWikiFarm|null [private] Singleton. */
	private static $self = null;
	
	/** @var string [private] Farm configuration directory. */
	private $configDir = '/etc/mediawiki';
	
	/** @var string|null [private] MediaWiki code directory, where each subdirectory is a MediaWiki installation. */
	private $codeDir = null;
	
	/** @var bool [private] This object cannot be used because of an emergency error. */
	public $unusable = false;
	
	/** @var array [private] Farm configuration file. */
	public $params = array();
	
	/** @var array [private] Variables related to the current request. */
	public $variables = array();
	
	
	
	/* 
	 * Public Methods
	 * -------------- */
	
	/**
	 * In the case of multiversion installations, this select the version directory.
	 * 
	 * This function is called very early during the loading, even before MediaWiki
	 * is loaded (first function called by multiversion-dedicated entry points like
	 * `www/index.php`).
	 * 
	 * @param string $entryPoint Name of the entry point, e.g. 'index.php', 'load.php'…
	 * @param string|null $host Requested host.
	 * @return string $entryPoint Identical entry point as passed in input.
	 */
	static function getEntryPoint( $entryPoint, $host = null ) {
		
		global $wgMediaWikiFarm;
		
		# Initialise object
		$wgMediaWikiFarm = self::initialise( $host );
		
		# Check existence
		if( !$wgMediaWikiFarm->checkExistence() ) {
			
			echo 'Error: unknown wiki.';
			exit;
		}
		
		# Go to version directory
		chdir( $wgMediaWikiFarm->params['code'] );
		
		return $entryPoint;
	}
	
	/**
	 * Initialise the unique object of type MediaWikiFarm.
	 * 
	 * @param string|null $host Requested host.
	 * @return MediaWikiFarm Singleton.
	 */
	static function initialise( $host = null ) {
		
		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir;
		
		if( self::$self == null ) {
			
			# Warning: do not use $GLOBALS['_SERVER']['HTTP_HOST']: bug with PHP7: it is not initialised in early times of a script
			if( is_null( $host ) ) $host = $_SERVER['HTTP_HOST'];
			self::$self = new self( $host, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir );
		}
		return self::$self;
	}
	
	/**
	 * Check the existence of the wiki, given variables values and files listing existing wikis.
	 * 
	 * A wiki exists if:
	 *   - variables with a file attached are defined, and
	 *   - a wikiID can be computed, and
	 *   - a version is found and does exist, and
	 *   - the various properties of the wiki are defined.
	 * 
	 * @return bool The wiki does exist.
	 */
	function checkExistence() {
		
		if( $this->unusable )
			return false;
		
		# In the multiversion case, informations are already loaded and nonexistent wikis are already verified
		if( array_key_exists( 'version', $this->params ) )
			return true;
		
		# Replace variables in the host name and possibly retrieve the version
		if( ($version = $this->replaceHostVariables()) === false )
			return false;
		
		# Set wikiID, the unique identifier of the wiki
		if( !$this->setWikiID() )
			return false;
		
		# Set the version of the wiki
		if( !$this->setVersion( $version ) )
			return false;
		
		# Set other properties of the wiki
		if( !$this->setWikiProperties() )
			return false;
		
		# Set available suffixes and wikis
		// This is not useful since nobody else use available suffixes and wikis
		// For now, remove loading of one config file to improve a bit performance
		//$this->setWgConf();
		
		return true;
	}
	
	/**
	 * This function loads MediaWiki configuration (parameters).
	 * 
	 * @return void
	 */
	function loadMediaWikiConfig() {
		
		if( $this->unusable )
			return false;
		
		if( !is_array( $this->params['globals'] ) )
			$this->getMediaWikiConfig();
		
		// Set general parameters as global variables
		foreach( $this->params['globals']['general'] as $setting => $value ) {
			
			$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * This function load the skins configuration (wfLoadSkin loading mechanism and parameters).
	 * 
	 * WARNING: it doesn’t load the skins with the require_once mechanism (it is not possible in
	 * a function because variables would inherit the non-global scope); such skins must be loaded
	 * before calling this function.
	 * 
	 * @return void
	 */
	function loadSkinsConfig() {
		
		if( $this->unusable )
			return false;
		
		// Load skins with the wfLoadSkin mechanism
		foreach( $this->params['globals']['skins'] as $skin => $value ) {
			
			if( $value['_loading'] == 'wfLoadSkin' )
			
				wfLoadSkin( $skin );
			
			unset( $this->params['globals']['skins'][$skin]['_loading'] );
		}
		
		// Set skin parameters as global variables
		foreach( $this->params['globals']['skins'] as $skin => $settings ) {
			
			foreach( $settings as $setting => $value )
				
				$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * This function load the skins configuration (wfLoadSkin loading mechanism and parameters).
	 * 
	 * WARNING: it doesn’t load the skins with the require_once mechanism (it is not possible in
	 * a function because variables would inherit the non-global scope); such skins must be loaded
	 * before calling this function.
	 * 
	 * @return void
	 */
	function loadExtensionsConfig() {
		
		if( $this->unusable )
			return false;
		
		// Load extensions with the wfLoadExtension mechanism
		foreach( $this->params['globals']['extensions'] as $extension => $value ) {
			
			if( $value['_loading'] == 'wfLoadExtension' )
				
				wfLoadExtension( $extension );
			
			unset( $this->params['globals']['extensions'][$extension]['_loading'] );
		}
		
		// Set extension parameters as global variables
		foreach( $this->params['globals']['extensions'] as $extension => $settings ) {
			
			foreach( $settings as $setting => $value )
				
				$GLOBALS[$setting] = $value;
		}
	}
	
	
	
	/*
	 * Private Methods
	 * --------------- */
	
	/**
	 * Construct a MediaWiki farm.
	 * 
	 * This constructor sets the directories (configuration and code) and select the right
	 * farm depending of the host (when there are multiple farms). In case of error (unreadable
	 * directory or file, or unrecognized host), no exception is thrown but the property
	 * 'unusable' becomes true.
	 * 
	 * @param string $host Requested host.
	 * @param string $configDir Configuration directory.
	 * @param string|null $codeDir Code directory; if null, the current MediaWiki installation is used.
	 */
	private function __construct( $host, $configDir = '/etc/mediawiki', $codeDir = null ) {
		
		# Check parameters
		if( !is_string( $host ) ||
		    !(is_string( $configDir ) && is_dir( $configDir )) ||
		    !(is_null( $codeDir ) xor (is_string( $codeDir ) && is_dir( $codeDir )))
		  ) {
		  	
			$this->unusable = true;
			return;
		}
		
		# Set parameters
		$this->configDir = $configDir;
		$this->codeDir = $codeDir;
		
		# Read the farm(s) configuration
		if( $configs = $this->readFile( 'farms.yml', $this->configDir ) );
		elseif( $configs = $this->readFile( '/farms.json', $this->configDir ) );
		elseif( $configs = $this->readFile( '/farms.php', $this->configDir ) );
		else $this->unusable = true;
		
		# Now select the right configuration amoung all farms
		$this->unusable = !$this->selectFarm( $configs, $host );
	}
	
	/**
	 * Select the farm.
	 * 
	 * @param array $configs All farm configurations.
	 * @param string $host Requested host.
	 * return bool One of the farm has been selected.
	 */
	private function selectFarm( $configs, $host ) {
		
		if( $this->unusable )
			return false;
		
		static $redirects = 0;
		if( $redirects >= 5 ) {
			$this->unusable = true;
			return false;
		}
		
		# Re-initialise some variables for the 'redirect' case
		$this->variables = array();
		
		# For each proposed farm, check if the host matches
		foreach( $configs as $regex => $config ) {
			
			if( !preg_match( '/' . $regex . '/i', $host, $matches ) )
				continue;
			
			# Initialise variables from the host
			foreach( $matches as $key => $value ) {
				
				if( is_string( $key ) )
					$this->variables[$key] = $value;
			}
			
			# Redirect to another farm
			if( array_key_exists( 'redirect', $config ) ) {
				
				$redirects++;
				return $this->selectFarm( $configs, $this->replaceVariables( $config['redirect'] ) );
			}
			
			# Get the selected configuration
			$this->params = $config;
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Replacement of the variables in the host name.
	 * 
	 * @return string|null|false If an existing version is found in files, returns a string; if no version is found, returns null; if the host is missing in existence files, returns false; if an existence file is missing or badly formatted, return false and turns this object into a unusable state.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function replaceHostVariables() {
		
		$version = null;
		
		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->params['variables'] as $variable ) {
			
			$key = $variable['variable'];
			
			# If the variable doesn’t exist, continue
			if( !array_key_exists( $key, $this->variables ) )
				continue;
			
			$value = $this->variables[$key];
			
			# If every values are correct, continue
			if( !array_key_exists( 'file', $variable ) )
				continue;
			
			# Really check if the variable is in the listing file
			$choices = $this->readFile( $this->replaceVariables( $variable['file'] ), $this->configDir );
			if( $choices === false ) {
				$this->unusable = true;
				return false;
			}
			
			# Check if the array is a simple list of wiki identifiers without version information…
			if( array_keys( $choices ) === range( 0, count( $choices ) - 1 ) ) {
				if( !in_array( $value, $choices ) )
					return false;
			
			# …or a dictionary with wiki identifiers and corresponding version information
			} else {
				
				if( !array_key_exists( $value, $choices ) )
					return false;
				
				if( is_string( $this->codeDir ) && is_dir( $this->codeDir . '/' . ((string) $choices[$value]) ) && is_file( $this->codeDir . '/' . ((string) $choices[$value]) . '/includes/DefaultSettings.php' ) )
					$version = (string) $choices[$value];
			}
		}
		
		return $version;
	}
	
	/**
	 * Computation of the suffix and wikiID.
	 * 
	 * This function is the central point to get the unique identifier of the wiki, wikiID.
	 * 
	 * @return bool The wikiID and suffix were set, and the wiki could exist.
	 */
	private function setWikiID() {
		
		$this->params['version'] = null;
		$this->params['globals'] = null;
		
		# Set suffix
		$this->setWikiProperty( 'suffix' );
		$this->variables['SUFFIX'] = $this->params['suffix'];
		
		# Set wikiID
		$this->setWikiProperty( 'wikiID' );
		$this->variables['WIKI'] = $this->params['wikiID'];
		
		# Check consistency
		if( !$this->params['suffix'] || !$this->params['wikiID'] ) {
			$this->unusable = true;
			return false;
		}
		
		return true;
	}
	
	/**
	 * Setting of the version, either from the input if already got, either from a file.
	 * 
	 * @param string|null $version If a string, this is the version already got, just set it.
	 * @return bool The version was set, and the wiki could exist.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function setVersion( $version = null ) {
		
		global $IP, $wgVersion;
		
		$this->setWikiProperty( 'versions' );
		
		# In the case multiversion is configured and version is already known
		if( is_string( $this->codeDir ) && is_string( $version ) )
			$this->params['code'] = $this->codeDir . '/' . $version;
		
		# In the case multiversion is configured, but version is not known as of now
		elseif( is_string( $this->codeDir ) && is_null( $version ) ) {
			
			$versions = $this->readFile( $this->params['versions'], $this->configDir );
			
			if( !$versions ) {
				$this->unusable = true;
				return false;
			}
			
			if( array_key_exists( $this->params['wikiID'], $versions ) && is_file( $this->codeDir . '/' . $versions[$this->params['wikiID']] . '/includes/DefaultSettings.php' ) )
				$version = $versions[$this->params['wikiID']];
			
			elseif( array_key_exists( $this->params['suffix'], $versions ) && is_file( $this->codeDir . '/' . $versions[$this->params['suffix']] . '/includes/DefaultSettings.php' ) )
				$version = $versions[$this->params['suffix']];
			
			elseif( array_key_exists( 'default', $versions ) && is_file( $this->codeDir . '/' . $versions['default'] . '/includes/DefaultSettings.php' ) )
				$version = $versions['default'];
			
			else return false;
			
			$this->params['code'] = $this->codeDir . '/' . $version;
		}
		
		# In the case no multiversion is configured
		elseif( is_null( $this->codeDir ) ) {
			
			$version = $wgVersion;
			$this->params['code'] = $IP;
		}
		else {
			$this->unusable = true;
			return false;
		}
		
		# Set the version in the wiki configuration and as a variable to be used later
		$this->variables['VERSION'] = $version;
		$this->params['version'] = $version;
		
		return true;
	}
	
	/**
	 * Computation of the properties, which could depend on the suffix, wikiID, or other variables.
	 * 
	 * @return bool The wiki properties were set, and the wiki could exist.
	 */
	private function setWikiProperties() {
		
		if( !array_key_exists( 'config', $this->params ) )
			$this->params['config'] = array();
		
		$this->setWikiProperty( 'data' );
		$this->setWikiProperty( 'config' );
		
		return true;
	}
	
	/**
	 * Set available suffixes and wikis.
	 * 
	 * @todo Still hacky: before setting parameters in stone in farms.yml, various configurations should be reviewed to select accordingly the rights management modelisation
	 * @return void
	 */
	/*private function setWgConf() {
		
		global $wgConf;
		
		$wgConf->suffixes = array( $this->params['suffix'] );
		$wikiIDs = $this->readFile( $this->params['suffix'] . '/wikis.yml', $this->configDir );
		foreach( array_keys( $wikiIDs ) as $wiki ) {
			$wgConf->wikis[] = $wiki . '-' . $this->params['suffix'];
		}
	}*/
	
	/**
	 * Get or compute the configuration (MediaWiki, skins, extensions) for a wiki.
	 * 
	 * This function uses a caching mechanism in order to avoid recomputing each time the
	 * configuration; it is rebuilt when origin configuration files are changed.
	 * 
	 * The returned array has the following format:
	 * array( 'general' => array( 'wgSitename' => 'Foo', ... ),
	 *        'skins' => array( '_loading' => 'wfLoadSkin'|'require_once',
	 *                          'wgFlowParsoidTimeout' => 100, ...
	 *                        ),
	 *        'extensions' => array( '_loading' => 'wfLoadExtension'|'require_once',
	 *                               'wgFlowParsoidTimeout' => 100, ...
	 *                             )
	 *      )
	 * 
	 * @return array Global parameter variables and loading mechanisms for skins and extensions.
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	private function getMediaWikiConfig() {
		
		global $wgConf;
		
		if( $this->unusable )
			return false;
		
		$myWiki = $this->params['wikiID'];
		$mySuffix = $this->params['suffix'];
		
		$cacheFile = $this->replaceVariables( '$VERSION-$SUFFIX-$WIKI.ser' );
		
		//var_dump($wgConf);
		//var_dump($cacheFile);
		//var_dump($myWiki);
		//var_dump($mySuffix);
		//echo "\n\n<br /><br />";
		
		$oldness = 0;
		foreach( $this->params['config'] as $configFile )
			$oldness = max( $oldness, @filemtime( $this->configDir . '/' . $configFile['file'] ) );
		
		$this->params['globals'] = false;
		
		if( array_key_exists( 'cache', $this->params ) && is_file( $this->configDir . '/.cache/' . $cacheFile ) && @filemtime( $this->configDir . '/.cache/' . $cacheFile ) >= $oldness )
			$this->params['globals'] = $this->readFile( $cacheFile, $this->configDir . '/.cache' );
		
		else {
			
			$this->params['globals'] = array(
				'general' => array(),
				'skins' => array(),
				'extensions' => array()
			);
			$globals =& $this->params['globals'];
			
			# Populate wgConf
			if( !$this->populatewgConf() )
			return false;
			
			# Get specific configuration for this wiki
			# Do not use SiteConfiguration::extractAllGlobals or the configuration caching would become
			# ineffective and there would be inconsistencies in this process
			$globals['general'] = $wgConf->getAll( $myWiki, $mySuffix );
			
			# For the permissions array, fix a small strangeness: when an existing default permission
			# is true, it is not possible to make it false in the specific configuration
			if( array_key_exists( '+wgGroupPermissions', $wgConf->settings ) )
				
				$globals['general']['wgGroupPermissions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgGroupPermissions', $myWiki, $mySuffix ), $globals['general']['wgGroupPermissions'] );
			
			//if( array_key_exists( '+wgDefaultUserOptions', $wgConf->settings ) )
				//$globals['general']['wgDefaultUserOptions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgDefaultUserOptions', $myWiki, $mySuffix ), $globals['general']['wgDefaultUserOptions'] );
			
			# Extract from the general configuration skin and extension configuration
			$this->extractSkinsAndExtensions();
			
			# Register this extension MediaWikiFarm to appear in Special:Version
			$globals['extensions']['MediaWikiFarm'] = array( '_loading' => 'wfLoadExtension' );
			
			# Save this configuration in a serialised file
			$this->cacheFile( $globals, $cacheFile );
		}
	}
	
	/**
	 * Popuplate wgConf from config files.
	 * 
	 * @return bool Success.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function populatewgConf() {
		
		global $wgConf;
		
		if( $this->unusable )
			return false;
		
		foreach( $this->params['config'] as $configFile ) {
			
			# Executable config files
			if( array_key_exists( 'exec', $configFile ) ) continue;
			
			$theseSettings = $this->readFile( $configFile['file'], $this->configDir );
			if( $theseSettings === false ) {
				$this->unusable = true;
				return false;
			}
			
			# Key 'default' => no choice of the wiki
			if( $configFile['key'] == 'default' ) {
				
				foreach( $theseSettings as $setting => $value ) {
					
					$wgConf->settings[$setting]['default'] = $value;
				}
			}
			
			# Key '*' => choice of any wiki
			elseif( $configFile['key'] == '*' ) {
				
				foreach( $theseSettings as $setting => $value ) {
					
					foreach( $value as $suffix => $val ) {
						
						$wgConf->settings[$setting][$suffix] = $val;
					}
				}
			}
			
			# Other key
			else {
				
				$defaultKey = null;
				if( array_key_exists( 'default', $configFile ) )
					$defaultKey = $this->replaceVariables( $configFile['default'] );
				$classicKey = $this->replaceVariables( $configFile['key'] );
				
				foreach( $theseSettings as $setting => $values ) {
					
					foreach( $values as $wiki => $val ) {
						
						if( $wiki == 'default' && $defaultKey ) $wgConf->settings[$setting][$defaultKey] = $val;
						else $wgConf->settings[$setting][str_replace( '*', $wiki, $classicKey )] = $val;
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Extract from the general configuration skin and extension configuration
	 * 
	 * @return void
	 */
	private function extractSkinsAndExtensions() {
		
		$globals =& $this->params['globals'];
		
		# Search for skin and extension activation
		$unsetPrefixes = array();
		foreach( $globals['general'] as $setting => $value ) {
			if( preg_match( '/^wgUseSkin(.+)$/', $setting, $matches ) && $value === true ) {
				
				$skin = $matches[1];
				$loadingMechanism = $this->detectLoadingMechanism( 'skin', $skin );
				
				if( is_null( $loadingMechanism ) ) $unsetPrefixes[] = $skin;
				else $globals['skins'][$skin] = array( '_loading' => $loadingMechanism );
				
				unset( $globals['general'][$setting] );
			}
			elseif( preg_match( '/^wgUseExtension(.+)$/', $setting, $matches ) && $value === true ) {
				
				$extension = $matches[1];
				$loadingMechanism = $this->detectLoadingMechanism( 'extension', $extension );
				
				if( is_null( $loadingMechanism ) ) $unsetPrefixes[] = $extension;
				else $globals['extensions'][$extension] = array( '_loading' => $loadingMechanism );
				
				unset( $globals['general'][$setting] );
			}
			elseif( preg_match( '/^wgUse(?:Skin|Extension|LocalExtension)(.+)$/', $setting, $matches ) && $value !== true ) {
				
				$unsetPrefixes[] = $matches[1];
				unset( $globals['general'][$setting] );
			}
		}
		
		# Extract skin and extension configuration from the general configuration
		$regexSkins = '/^wg(' . implode( '|',
			array_map(
				function( $a ) { return preg_quote( $a, '/' ); },
				array_keys( $globals['skins'] )
			)
		) . ')/';
		$regexExtensions = '/^wg(' . implode( '|',
			array_map(
				function( $a ) { return preg_quote( $a, '/' ); },
				array_keys( $globals['extensions'] )
			)
		) . ')/';
		$regexUnsetPrefixes = '/^wg(' . implode( '|',
			array_map(
				function( $a ) { return preg_quote( $a, '/' ); },
				$unsetPrefixes
			)
		) . ')/';
		foreach( $globals['general'] as $setting => $value ) {
			
			if( preg_match( $regexSkins, $setting, $matches ) ) {
				$globals['skins'][$matches[1]][$setting] = $value;
				unset( $setting );
			}
			elseif( preg_match( $regexExtensions, $setting, $matches ) ) {
				$globals['extensions'][$matches[1]][$setting] = $value;
				unset( $setting );
			}
			elseif( preg_match( $regexUnsetPrefixes, $setting, $matches ) )
				unset( $matches[1] );
		}
	}
	
	/**
	 * Detection of the loading mechanism of extensions and skins.
	 * 
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @return string|null Loading mechnism in ['wfLoadExtension', 'wfLoadSkin', 'require_once', 'composer'] or null if all mechanisms failed.
	 */
	private function detectLoadingMechanism( $type, $name ) {
		
		if( !is_dir( $this->params['code'].'/'.$type.'s/'.$name ) )
			return null;
		
		# An extension.json/skin.json file is in the directory -> assume it is the loading mechanism
		if( is_file( $this->params['code'].'/'.$type.'s/'.$name.'/'.$type.'.json' ) )
			return 'wfLoad'.ucfirst($type);
		
		# A MyExtension.php file is in the directory -> assume it is the loading mechanism
		elseif( is_file( $this->params['code'].'/'.$type.'s/'.$name.'/'.$name.'.php' ) )
			return 'require_once';
		
		# A composer.json file is in the directory -> assume it is the loading mechanism if previous mechanisms didn’t succeed
		elseif( is_file( $this->params['code'].'/'.$type.'s/'.$name.'/composer.json' ) )
			return 'composer';
		
		return null;
	}
	
	
	
	/*
	 * Helper Methods
	 * -------------- */
	
	/**
	 * Read a file either in PHP, YAML (if library available), JSON, dblist, or serialised, and returns the interpreted array.
	 * 
	 * The choice between the format depends on the extension: php, yml, yaml, json, dblist, serialised.
	 * 
	 * @param string $filename Name of the requested file.
	 * @param string $directory Parent directory.
	 * @return array|false The interpreted array in case of success, else false.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	private function readFile( $filename, $directory = '' ) {
		
		# Check parameter
		if( !is_string( $filename ) )
			return false;
		
		# Detect the format
		# Note the regex must be greedy to correctly select double extensions
		$format = preg_replace( '/^.*\.([a-z]+)$/', '$1', $filename );
		
		# Check the file exists
		$prefixedFile = $directory ? $directory . '/' . $filename : $filename;
		if( !is_file( $prefixedFile ) )
			return false;
		
		# Format PHP
		if( $format == 'php' )
			
			$array = @include $prefixedFile;
		
		# Format 'serialisation'
		elseif( $format == 'ser' ) {
			
			$content = @file_get_contents( $prefixedFile );
			
			if( !$content )
				return array();
			
			$array = @unserialize( $content );
		}
		
		# Cached version
		elseif( is_file( $this->configDir . '/.cache/' . $filename . '.ser' ) && @filemtime( $this->configDir . '/.cache/' . $filename . '.ser' ) >= @filemtime( $prefixedFile ) )
			
			return $this->readFile( $filename . '.ser', $this->configDir . '/.cache' );
		
		# Format YAML
		elseif( $format == 'yml' || $format == 'yaml' ) {
			
			# Load Composer libraries
			# There is no warning if not present because to properly handle the error by returning false
			# This is only included here to avoid delays (~3ms) during the loading using cached files or other formats
			@include_once __DIR__ . '/../vendor/autoload.php';
			
			if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) || !class_exists( 'Symfony\Component\Yaml\Exception\ParseException' ) )
				return false;
			
			try {
				$array = Symfony\Component\Yaml\Yaml::parse( @file_get_contents( $prefixedFile ) );
			}
			catch( Symfony\Component\Yaml\Exception\ParseException $e ) {
				
				return false;
			}
		}
		
		# Format JSON
		elseif( $format == 'json' )
			
			$array = json_decode( @file_get_contents( $prefixedFile ), true );
		
		# Format 'dblist' (simple list of strings separated by newlines)
		elseif( $format == 'dblist' ) {
			
			$content = @file_get_contents( $prefixedFile );
			
			if( !$content )
				return array();
			
			return explode( "\n", $content );
		}
		
		# Error for any other format
		else return false;
		
		# A null value is an empty file or value 'null'
		if( is_null( $array ) )
			$array = array();
		
		# Regular return for arrays
		if( is_array( $array ) ) {
			
			if( $format !== 'php' && $format !== 'ser' )
				$this->cacheFile( $array, $filename.'.ser' );
			
			return $array;
		}
		
		# Error for any other type
		return false;
	}
	
	/**
	 * Create a cache file.
	 * 
	 * @param array $array Array of the data to be cached.
	 * @param string $filename Name of the cache file; this filename must have an extension '.ser' or '.php' else no cache file is saved.
	 * @return void
	 */
	private function cacheFile( $array, $filename ) {
		
		$prefixedFile = $this->configDir . '/.cache/' . $filename;
		
		# Create temporary file
		@mkdir( dirname( $prefixedFile ) );
		$tmpFile = $prefixedFile . '.tmp';
		
		if( preg_match( '/\.ser$/', $filename ) ) {
			if( file_put_contents( $tmpFile, serialize( $array ) ) ) {
				rename( $tmpFile, $prefixedFile );
			}
		}
		elseif( preg_match( '/\.php$/', $filename ) ) {
			if( file_put_contents( $tmpFile, "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn ".var_export( $array, true ).';' ) )
				rename( $tmpFile, $prefixedFile );
		}
	}
	
	/**
	 * Set a wiki property and replace placeholders (property name version).
	 * 
	 * @param string $name Name of the property.
	 * @return void
	 */
	private function setWikiProperty( $name ) {
		
		if( !array_key_exists( $name, $this->params ) )
			return;
		
		$this->params[$name] = $this->replaceVariables( $this->params[$name] );
	}
	
	/**
	 * Replace variables in a string.
	 * 
	 * @param string|null $value Value of the property.
	 * @return string Input where variables were replaced.
	 */
	private function replaceVariables( $value ) {
		
		static $rkeys = array(), $rvalues = array();
		if( count( $this->variables ) != count( $rkeys ) ) {
			
			$rkeys = array();
			$rvalues = array();
			
			foreach( $this->variables as $key => $val ) {
				$rkeys[] = '$' . $key;
				$rvalues[] = $val;
			}
		}
		
		if( is_null( $value ) )
			return '';
		
		elseif( is_string( $value ) )
			$value = str_replace( $rkeys, $rvalues, $value );
		
		elseif( !is_array( $value ) ) {
			
			$this->unusable = true;
			return '';
		}
		elseif( is_array( $value ) ) {
			
			foreach( $value as &$subvalue ) {
				foreach( $subvalue as &$subsubvalue )
					$subsubvalue = str_replace( $rkeys, $rvalues, $subsubvalue );
			}
		}
		
		return $value;
	}
	
	/**
	 * Merge multiple arrays together.
	 * On encountering duplicate keys, merge the two, but ONLY if they're arrays.
	 * PHP's array_merge_recursive() merges ANY duplicate values into arrays,
	 * which is not fun
	 * This function is almost the same as SiteConfiguration::arrayMerge, with the
	 * difference an existing scalar value has precedence EVEN if evaluated to false,
	 * in order to override permissions array with removed rights.
	 *
	 * @param array $array1.
	 * @return array
	 * @SuppressWarning(PHPMD.StaticAccess)
	 */
	static private function arrayMerge( $array1/* ... */ ) {
		$out = $array1;
		$argsCount = func_num_args();
		for ( $i = 1; $i < $argsCount; $i++ ) {
			foreach ( func_get_arg( $i ) as $key => $value ) {
				if( array_key_exists( $key, $out ) && is_array( $out[$key] ) && is_array( $value ) ) {
					$out[$key] = self::arrayMerge( $out[$key], $value );
				} elseif( !array_key_exists( $key, $out ) && !is_numeric( $key ) ) {
					// Values that evaluate to true given precedence, for the
					// primary purpose of merging permissions arrays.
					$out[$key] = $value;
				} elseif( is_numeric( $key ) ) {
					$out[] = $value;
				}
			}
		}
		
		return $out;
	}
}

