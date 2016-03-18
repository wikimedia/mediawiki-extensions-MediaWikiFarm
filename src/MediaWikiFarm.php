<?php
/**
 * Class MediaWikiFarm.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) ) exit;

require_once __DIR__ . '/../vendor/autoload.php';

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
	public static $self = null;
	
	/** @var string [private] Farm configuration directory. */
	public $configDir = '/etc/mediawiki';
	
	/** @var string|null [private] MediaWiki code directory, where each subdirectory is a MediaWiki installation. */
	public $codeDir = null;
	
	/** @var bool [private] This object cannot be used because of an emergency error. */
	public $unusable = false;
	
	/** @var array [private] Farm configuration file. */
	public $config = array();
	
	/** @var array [private] Selected MediaWiki version. */
	public $wiki = array();
	
	
	
	/* 
	 * Public Methods
	 * -------------- */
	
	/**
	 * Initialise the unique object of type MediaWikiFarm.
	 * 
	 * @param string 
	 * @return MediaWikiFarm Singleton.
	 */
	static function initialise( $host ) {
		
		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir;
		
		if( self::$self == null )
			self::$self = new self( $host, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir );
		
		return self::$self;
	}
	
	/**
	 * Check the existence of the wiki, given variables values and files listing existing wikis.
	 * 
	 * A wiki exists if all variables are defined in the URL and all values are found in the
	 * corresponding listing file. Files can be in PHP, YAML, or dblist.
	 * 
	 * @return bool The wiki exists.
	 */
	function checkExistence() {
		
		global $IP;
		
		if( $this->unusable )
			return false;
		
		$keys = array();
		$values = array();
		$version = null;
		
		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->config['variables'] as $variable ) {
			
			$key = $variable['variable'];
			
			# The variable must exist
			if( !array_key_exists( $key, $this->variables ) )
				return false;
			
			# Possibly the variables up to this one can be placeholders in filenames
			$value = $this->variables[$key];
			$keys[] = '/\$' . preg_quote( $key, '/' ) . '/';
			$values[] = $value;
			
			# If every value is correct, continue
			if( !array_key_exists( 'file', $variable ) )
				continue;
			
			# Really check if the variable is in the listing file
			$choices = $this->readFile( $this->configDir . '/' . preg_replace( $keys, $values, $variable['file'] ) );
			if( $choices === false )
				return false;
			
			# Check if the array is a simple list of wiki identifiers without version information…
			if( array_keys( $choices ) === range( 0, count( $choices ) - 1 ) ) {
				if( !in_array( $value, $choices ) )
					return false;
			
			# …or a dictionary with wiki identifiers and corresponding version information
			} else {
				
				if( !array_key_exists( $value, $choices ) )
					return false;
				
				if( isset( $this->codeDir ) && is_dir( $this->codeDir . '/' . ((string) $choices[$value]) ) && is_file( $this->codeDir . '/' . ((string) $choices[$value]) . '/includes/DefaultSettings.php' ) )
					$version = (string) $choices[$value];
			}
		}
		
		# When the wiki is found, replace all variables in other configuration files
		$keys[] = '/\$version/';
		$values[] = $version ? $version : '';
		
		$this->wiki = $this->config;
		$this->wiki['version'] = $version;
		$this->wiki['globals'] = null;
		if( isset( $version ) )
			$this->wiki['code'] = $this->codeDir . (isset( $version ) ? '/' . $version : '');
		else
			$this->wiki['code'] = $IP;
		$this->wiki['data'] = preg_replace( $keys, $values, $this->wiki['data'] );
		if( array_key_exists( 'cache', $this->wiki ) ) $this->wiki['cache'] = preg_replace( $keys, $values, $this->wiki['cache'] );
		if( array_key_exists( 'config', $this->wiki ) ) {
			
			if( is_string( $this->wiki['config'] ) )
				$this->wiki['config'] = array( $this->wiki['config'] );
			
			foreach( $this->wiki['config'] as &$configFile )
				$configFile = preg_replace( $keys, $values, $configFile );
		}
		if( array_key_exists( 'post-config', $this->wiki ) ) {
			
			if( is_string( $this->wiki['post-config'] ) )
				$this->wiki['post-config'] = array( $this->wiki['post-config'] );
			
			foreach( $this->wiki['post-config'] as &$configFile )
				$configFile = preg_replace( $keys, $values, $configFile );
		}
		
		foreach( $this->wiki['variables'] as &$variable ) {
			
			if( array_key_exists( 'file', $variable ) ) $variable['file'] = preg_replace( $keys, $values, $variable['file'] );
			if( array_key_exists( 'config', $variable ) ) {
				
				if( is_string( $variable['config'] ) )
					$variable['config'] = array( $variable['config'] );
				
				foreach( $variable['config'] as &$configFile )
					$configFile = preg_replace( $keys, $values, $configFile );
			}
			if( array_key_exists( 'post-config', $variable ) ) {
				
				if( is_string( $variable['post-config'] ) )
					$variable['post-config'] = array( $variable['post-config'] );
				
				foreach( $variable['post-config'] as &$configFile )
					$configFile = preg_replace( $keys, $values, $configFile );
			}
		}
		
		return true;
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
		if( !isset( $host ) || !is_string( $host ) )
			$this->unusable = true;
		if( isset( $configDir ) && (!is_string( $configDir ) || !is_dir( $configDir )) )
			$this->unusable = true;
		if( isset( $codeDir ) && (!is_string( $codeDir ) || !is_dir( $codeDir )) )
			$this->unusable = true;
		
		if( $this->unusable ) return;
		
		# Set parameters
		$this->configDir = $configDir;
		$this->codeDir = $codeDir;
		
		# Read the farm(s) configuration
		if( $configs = $this->readFile( $this->configDir . '/farms.yml' ) );
		else if( $configs = $this->readFile( $this->configDir . '/farms.php' ) );
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
		
		# Check parameters
		if( !isset( $configs ) || !is_array( $configs ) )
			return false;
		
		if( !isset( $host ) || !is_string( $host ) )
			return false;
		
		# For each proposed farm, check if the host matches
		foreach( $configs as $regex => $config ) {
			
			if( preg_match( '/' . $regex . '/', $host, $matches ) ) {
				
				# Get the selected configuration
				$this->config = $config;
				$this->variables = array();
				
				# Initialise variables from the host
				foreach( $this->config['variables'] as $variable ) {
					
					if( array_key_exists( $variable['variable'], $matches ) )
						$this->variables[$variable['variable']] = $matches[$variable['variable']];
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	
	
	/*
	 * Helper Methods (public)
	 * ----------------------- */
	
	/**
	 * Read a file either in PHP, YAML, or dblist, and returns the interpreted array.
	 * 
	 * The choice between the format depends on the extension: php, yml, dblist.
	 * 
	 * @param string $filename Name of the requested file.
	 * @return array|false The interpreted array in case of success, else false.
	 */
	function readFile( $filename ) {
		
		# Check parameter
		if( !is_string( $filename ) || !is_file( $filename ) )
			return false;
		
		# Detect the format
		# Note the regex must be greedy to correctly select double extensions
		$format = preg_replace( '/^.*\.([a-z]+)$/', '$1', $filename );
		
		if( $format == 'php' )
			return require $filename;
		
		if( $format == 'yml' ) {
			
			try {
				
				return \Symfony\Component\Yaml\Yaml::parse( file_get_contents( $filename ) );
			}
			catch( \Symfony\Component\Yaml\Exception\ParseException $e ) {
				
				return false;
			}
		}
		
		if( $format == 'dblist' )
			return explode( "\n", file_get_contents( $filename ) );
		
		return false;
	}
	
	/**
	 * Get or compute the configuration (MediaWiki, skins, extensions) for a wiki.
	 * 
	 * You have to specify the wiki, the suffix, and the version and, as parameters, the configuration
	 * and code directories and the caching file. This function uses a caching mechanism to avoid
	 * recompute each time the configuration; it is rebuilt when origin configuration files are changed.
	 * 
	 * The params argument should have the following keys:
	 * - 'configDir' (string) Configuration directory where are the various configuration files
	 * - 'codeDir' (string) Code directory where MediaWiki code is
	 * - 'cacheFile' (string) Template filename of the caching file
	 * - 'generalYamlFilename' (string) Path for the general YAML file, relative to configDir
	 * - 'suffixedYamlFilename' (string) Path for the suffixed YAML file, relative to configDir
	 * - 'privateYamlFilename' (string) Path for the privale YAML file, relative to configDir
	 * In cacheFile and suffixedYamlFilename, the string '$suffix' will be replaced by the actual
	 * suffix, and in cacheFile, the strings '$wiki' and '$version' will be replaced by the actual
	 * wiki identifier and the version.
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
	 * @param string $wiki Name of the wiki.
	 * @param string $suffix Suffix of the wiki (main family type).
	 * @param SiteConfiguration $wgConf SiteConfigurat object from MediaWiki.
	 * @return array Global parameter variables and loading mechanisms for skins and extensions.
	 */
	function getMediaWikiConfig( $myWiki, $mySuffix, &$wgConf ) {
		
		if( $this->unusable )
			return false;
		
		$codeDir = $this->wiki['code'];
		$cacheFile = $this->wiki['cache'];
		$generalYamlFilename = '/'.$this->wiki['config'][0];
		$suffixedYamlFilename = '/'.$this->wiki['variables'][0]['config'][0];
		$privateYamlFilename = '/'.$this->wiki['post-config'][0];
		
		//var_dump($codeDir);
		//var_dump($myWiki);
		//var_dump($mySuffix);
		//var_dump($generalYamlFilename);
		//var_dump($suffixedYamlFilename);
		//var_dump($privateYamlFilename);
		//echo "\n\n<br /><br />";
		
		$globals = false;
		
		if( @filemtime( $cacheFile ) >= max( filemtime( $this->configDir.$generalYamlFilename ),
			                                 filemtime( $this->configDir.$suffixedYamlFilename ),
			                                 filemtime( $this->configDir.$privateYamlFilename ) ) )
		{	
			$cache = @file_get_contents( $cacheFile );
			if ( $cache !== false ) {
				$globals = unserialize( $cache );
			}
		}
		else {
			
			$globals = array();
			$globals['general'] = array();
			$globals['skins'] = array();
			$globals['extensions'] = array();
			
			#$configFiles = $this->wiki['config'];
			#foreach( $this->wiki['variables'] as $variable )
			#	
			
			// Load InitialiseSettings.yml (general)
			$generalSettings = $this->readFile( $this->configDir.$generalYamlFilename );
			foreach( $generalSettings as $setting => $value ) {
				
				$wgConf->settings[$setting]['default'] = $value;
			}
			
			// Load InitialiseSettings.yml (client)
			$suffixedSettings = $this->readFile( $this->configDir.$suffixedYamlFilename );
			foreach( $suffixedSettings as $setting => $values ) {
				
				foreach( $values as $wiki => $val ) {
					
					if( $wiki == 'default' ) $wgConf->settings[$setting][$mySuffix] = $val;
					else $wgConf->settings[$setting][$wiki.'-'.$mySuffix] = $val;
				}
			}
			
			// Load PrivateSettings.yml (general)
			$privateSettings = $this->readFile( $this->configDir.$privateYamlFilename );
			foreach( $privateSettings as $setting => $value ) {
				
				foreach( $value as $suffix => $val ) {
					
					$wgConf->settings[$setting][$suffix] = $val;
				}
			}
			
			// Get specific configuration for this wiki
			// Do not use SiteConfiguration::extractAllGlobals or the configuration caching would become
			// ineffective and there would be inconsistencies in this process
			$globals['general'] = $wgConf->getAll( $myWiki.'-'.$mySuffix, $mySuffix );
			
			// For the permissions array, fix a small strangeness: when an existing default permission
			// is true, it is not possible to make it false in the specific configuration
			if( array_key_exists( '+wgGroupPermissions', $wgConf->settings ) )
				
				$globals['general']['wgGroupPermissions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgGroupPermissions', $myWiki.'-'.$mySuffix, $mySuffix ), $globals['general']['wgGroupPermissions'] );
			
			//if( array_key_exists( '+wgDefaultUserOptions', $wgConf->settings ) )
				//$globals['general']['wgDefaultUserOptions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgDefaultUserOptions', $myWiki.'-'.$mySuffix, $mySuffix ), $globals['general']['wgDefaultUserOptions'] );
			
			// Extract from the general configuration skin and extension configuration
			// Search for skin and extension activation
			$unsetPrefixes = array();
			foreach( $globals['general'] as $setting => $value ) {
				if( preg_match( '/^wgUseSkin(.+)$/', $setting, $matches ) && $value === true ) {
					
					$skin = $matches[1];
					if( is_dir( $codeDir.'/skins/'.$skin ) ) {
						
						$globals['skins'][$skin] = array();
						
						if( is_file( $codeDir.'/skins/'.$skin.'/skin.json' ) ) {
							$globals['skins'][$skin]['_loading'] = 'wfLoadSkin';
						}
						elseif( is_file( $codeDir.'/skins/'.$skin.'/'.$skin.'.php' ) ) {
							$globals['skins'][$skin]['_loading'] = 'require_once';
						}
						elseif( is_file( $codeDir.'/skins/'.$skin.'/composer.json' ) ) {
							$globals['skins'][$skin]['_loading'] = 'composer';
						}
						else {echo ' (unknown)';$unsetPrefixes[] = $skin;}
					}
					else $unsetPrefixes[] = $skin;
					
					unset( $globals['general'][$setting] );
				}
				elseif( preg_match( '/^wgUseExtension(.+)$/', $setting, $matches ) && $value === true ) {
					
					$extension = $matches[1];
					if( is_dir( $codeDir.'/extensions/'.$extension ) ) {
						
						$globals['extensions'][$extension] = array();
						if( is_file( $codeDir.'/extensions/'.$extension.'/extension.json' ) && $extension !== 'VisualEditor' ) {
							$globals['extensions'][$extension]['_loading'] = 'wfLoadExtension';
						}
						elseif( is_file( $codeDir.'/extensions/'.$extension.'/'.$extension.'.php' ) ) {
							$globals['extensions'][$extension]['_loading'] = 'require_once';
						}
						elseif( is_file( $codeDir.'/extensions/'.$extension.'/composer.json' ) ) {
							$globals['extensions'][$extension]['_loading'] = 'composer';
						}
						else $unsetPrefixes[] = $extension;
					}
					else $unsetPrefixes[] = $extension;
					
					unset( $globals['general'][$setting] );
				}
				elseif( preg_match( '/^wgUse(?:Skin|Extension|LocalExtension)(.+)$/', $setting, $matches ) && $value !== true ) {
					
					$unsetPrefixes[] = $matches[1];
					unset( $globals['general'][$setting] );
				}
			}
			
			// Extract from the general configuration skin and extension configuration
			$skins = array_keys( $globals['skins'] );
			$extensions = array_keys( $globals['extensions'] );
			foreach( $globals['general'] as $setting => $value ) {
				
				$found = false;
				foreach( $extensions as $extension ) {
					if( preg_match( '/^wg'.preg_quote($extension,'/').'/', $setting ) ) {
						$globals['extensions'][$extension][$setting] = $value;
						unset( $setting );
						$found = true;
						break;
					}
				}
				if( !$found ) {
					foreach( $skins as $skin ) {
						if( preg_match( '/^wg'.preg_quote($skin,'/').'/', $setting ) ) {
							$globals['skins'][$skin][$setting] = $value;
							unset( $setting );
							$found = true;
							break;
						}
					}
				}
				if( !$found ) {
					foreach( $unsetPrefixes as $prefix ) {
						if( preg_match( '/^wg'.preg_quote($prefix,'/').'/', $setting ) ) {
							unset( $setting );
							break;
						}
					}
				}
			}
			
			// Save this configuration in a serialised file
			@mkdir( dirname( $cacheFile ) );
			$tmpFile = tempnam( dirname( $cacheFile ), basename( $cacheFile ).'.tmp' );
			chmod( $tmpFile, 0640 );
			if( $tmpFile && file_put_contents( $tmpFile, serialize( $globals ) ) ) {
				rename( $tmpFile, $cacheFile );
			}
		}
		
		$this->wiki['globals'] = $globals;
		
		return $globals;
	}
	
	/**
	 * This function loads MediaWiki configuration (parameters).
	 * 
	 * @return void
	 */
	function loadMediaWikiConfig() {
		
		if( $this->unusable )
			return false;
		
		// Set general parameters as global variables
		foreach( $this->wiki['globals']['general'] as $setting => $value ) {
			
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
		foreach( $this->wiki['globals']['skins'] as $skin => $value ) {
			
			if( $value['_loading'] == 'wfLoadSkin' )
			
				wfLoadSkin( $skin );
			
			unset( $skins[$skin]['_loading'] );
		}
		
		// Set skin parameters as global variables
		foreach( $this->wiki['globals']['skins'] as $skin => $settings ) {
			
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
		foreach( $this->wiki['globals']['extensions'] as $extension => $value ) {
			
			if( $value['_loading'] == 'wfLoadExtension' )
				
				wfLoadExtension( $extension );
			
			unset( $extensions[$extension]['_loading'] );
		}
		
		// Set extension parameters as global variables
		foreach( $this->wiki['globals']['extensions'] as $extension => $settings ) {
			
			foreach( $settings as $setting => $value )
				
				$GLOBALS[$setting] = $value;
		}
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
	 *
	 * @return array
	 */
	static function arrayMerge( $array1/* ... */ ) {
		$out = $array1;
		$argsCount = func_num_args();
		for ( $i = 1; $i < $argsCount; $i++ ) {
			foreach ( func_get_arg( $i ) as $key => $value ) {
				if ( isset( $out[$key] ) && is_array( $out[$key] ) && is_array( $value ) ) {
					$out[$key] = MediaWikiFarm::arrayMerge( $out[$key], $value );
				} elseif ( !isset( $out[$key] ) && !is_numeric( $key ) ) {
					// Values that evaluate to true given precedence, for the
					// primary purpose of merging permissions arrays.
					$out[$key] = $value;
				} elseif ( is_numeric( $key ) ) {
					$out[] = $value;
				}
			}
		}
		
		return $out;
	}
}

