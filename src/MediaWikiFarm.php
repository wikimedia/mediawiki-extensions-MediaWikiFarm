<?php
/**
 * Class MediaWikiFarm.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

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
	
	/** @var string|null [private] Farm configuration directory. */
	public $configDir = null;
	
	/** @var array [private] Farm configuration file. */
	public $config = array();
	
	/** @var bool [private] This object cannot be used because of an emergency error. */
	public $unusable = false;
	
	
	
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
		
		global $wgMediaWikiFarmConfigDir;
		
		if( self::$self == null )
			self::$self = new self( $host, $wgMediaWikiFarmConfigDir );
		
		var_dump( self::$self->config );
		
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
		
		$keys = array();
		$values = array();
		
		foreach( $this->config['variables'] as $variable ) {
			
			$key = $variable['variable'];
			if( !array_key_exists( $key, $this->variables ) )
				return false;
			
			$value = $this->variables[$key];
			$keys[] = '/\$' . preg_quote( $key, '/' ) . '/';
			$values[] = $value;
			
			$choices = $this->readFile( preg_replace( $keys, $values, $this->configDir.'/'.$variable['file'] ) );
			if( $choices === false )
				return false;
			
			$isNumeric = array_keys( $choices ) === range( 0, count( $choices ) - 1 );
			if( $isNumeric && !in_array( $value, $choices ) )
				return false;
			
			if( !$isNumeric && !array_key_exists( $value, $choices ) )
				return false;
		}
		
		return true;
	}
	
	
	
	/*
	 * Private Methods
	 * --------------- */
	
	/**
	 * Construct a MediaWiki farm.
	 * 
	 * @param string $host Requested host.
	 * @param string|null $configDir Configuration directory; if null or not a string, the default value is used (/etc/mediawiki).
	 */
	private function __construct( $host, $configDir = null ) {
		
		# Check parameters
		if( !isset( $host ) || !is_string( $host ) || (isset( $configDir ) && !is_string( $configDir )) )
			$this->unusable = true;
		
		# Get parameters
		$this->configDir = is_string( $configDir ) ? $configDir : '/etc/mediawiki';
		
		# Read the farm configuration
		if( $configs = $this->readFile( $this->configDir . '/farms.yml' ) );
		else if( $configs = $this->readFile( $this->configDir . '/farms.php' ) );
		else $this->unusable = true;
		
		# Now select the right configuration amoung all farms
		if( !$this->unusable )
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
	 * @param string $version Version of the wiki.
	 * @param SiteConfiguration $wgConf SiteConfigurat object from MediaWiki.
	 * @param $array params Parameters for this configuration management.
	 * @return array Global parameter variables and loading mechanisms for skins and extensions.
	 */
	static function getMediaWikiConfig( $myWiki, $mySuffix, $myVersion, &$wgConf, $params ) {
		
		$codeDir = $params['codeDir'];
		$cacheFile = $params['cacheFile'];
		$generalYamlFilename = $params['generalYamlFilename'];
		$suffixedYamlFilename = $params['suffixedYamlFilename'];
		$privateYamlFilename = $params['privateYamlFilename'];
		
		$cacheFile = preg_replace( array( '/\$wiki/', '/\$suffix/', '/\$version/' ),
			                       array( $myWiki, $mySuffix, $myVersion ),
			                       $cacheFile );
		
		$suffixedYamlFilename = preg_replace( '/\$suffix/', $mySuffix, $suffixedYamlFilename );
		
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
			
			// Load InitialiseSettings.yml (general)
			$generalSettings = Yaml::parse( file_get_contents( $this->configDir.$generalYamlFilename ) );
			foreach( $generalSettings as $setting => $value ) {
				
				$wgConf->settings[$setting]['default'] = $value;
			}
			
			// Load InitialiseSettings.yml (client)
			$suffixedSettings = Yaml::parse( file_get_contents( $this->configDir.$suffixedYamlFilename ) );
			foreach( $suffixedSettings as $setting => $values ) {
				
				foreach( $values as $wiki => $val ) {
					
					if( $wiki == 'default' ) $wgConf->settings[$setting][$mySuffix] = $val;
					else $wgConf->settings[$setting][$wiki.'-'.$mySuffix] = $val;
				}
			}
			
			// Load PrivateSettings.yml (general)
			$privateSettings = Yaml::parse( file_get_contents( $this->configDir.$privateYamlFilename ) );
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
					if( is_dir( $codeDir.'/'.$myVersion.'/skins/'.$skin ) ) {
						
						$globals['skins'][$skin] = array();
						if( is_file( $codeDir.'/'.$myVersion.'/skins/'.$skin.'/skin.json' ) ) {
							$globals['skins'][$skin]['_loading'] = 'wfLoadSkin';
						}
						elseif( is_file( $codeDir.'/'.$myVersion.'/skins/'.$skin.'/'.$skin.'.php' ) ) {
							$globals['skins'][$skin]['_loading'] = 'require_once';
						}
						elseif( is_file( $codeDir.'/'.$myVersion.'/skins/'.$skin.'/composer.json' ) ) {
							$globals['skins'][$skin]['_loading'] = 'composer';
						}
						else {echo ' (unknown)';$unsetPrefixes[] = $skin;}
					}
					else $unsetPrefixes[] = $skin;
					
					unset( $globals['general'][$setting] );
				}
				elseif( preg_match( '/^wgUseExtension(.+)$/', $setting, $matches ) && $value === true ) {
					
					$extension = $matches[1];
					if( is_dir( $codeDir.'/'.$myVersion.'/extensions/'.$extension ) ) {
						
						$globals['extensions'][$extension] = array();
						if( is_file( $codeDir.'/'.$myVersion.'/extensions/'.$extension.'/extension.json' ) && $extension !== 'VisualEditor' ) {
							$globals['extensions'][$extension]['_loading'] = 'wfLoadExtension';
						}
						elseif( is_file( $codeDir.'/'.$myVersion.'/extensions/'.$extension.'/'.$extension.'.php' ) ) {
							$globals['extensions'][$extension]['_loading'] = 'require_once';
						}
						elseif( is_file( $codeDir.'/'.$myVersion.'/extensions/'.$extension.'/composer.json' ) ) {
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
		
		return $globals;
	}
	
	/**
	 * This function loads MediaWiki configuration (parameters).
	 * 
	 * @param array $extensions Subarray of general parameters (c.f. MediaWikiFarm:getMediaWikiConfig).
	 */
	static function loadMediaWikiConfig( $settings ) {
		
		// Set general parameters as global variables
		foreach( $settings as $setting => $value ) {
			
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
	 * @param array $extensions Subarray of skins parameters (c.f. MediaWikiFarm::getMediaWikiConfig).
	 */
	static function loadSkinsConfig( $skins ) {
		
		// Load skins with the wfLoadSkin mechanism
		foreach( $skins as $skin => $value ) {
			
			if( $value['_loading'] == 'wfLoadSkin' )
			
				wfLoadSkin( $skin );
			
			unset( $skins[$skin]['_loading'] );
		}
		
		// Set skin parameters as global variables
		foreach( $skins as $skin => $settings ) {
			
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
	 * @param array $extensions Subarray of extensions parameters (c.f. MediaWikiFarm::getMediaWikiConfig).
	 */
	static function loadExtensionsConfig( $extensions ) {
		
		// Load extensions with the wfLoadExtension mechanism
		foreach( $extensions as $extension => $value ) {
			
			if( $value['_loading'] == 'wfLoadExtension' )
				
				wfLoadExtension( $extension );
			
			unset( $extensions[$extension]['_loading'] );
		}
		
		// Set extension parameters as global variables
		foreach( $extensions as $extension => $settings ) {
			
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


/**
 * Load skins with the require_once mechanism.
 * 
 * @param array $skins List of skins to be loaded.
 * @return void
 */
function MediaWikiFarm_loadSkinsConfig( $skins ) {
	
	foreach( $skins as $skin => $value ) {
		
		if( $value['_loading'] == 'require_once' )
			require_once "$IP/skins/$skin/$skin.php";
	}
}

/**
 * Load extensions with the require_once mechanism.
 * 
 * @param array $extensions List of extensions to be loaded.
 * @return void
 */
function MediaWikiFarm_loadExtensionsConfig( $extensions ) {
	
	foreach( $extensions as $extension => $value ) {
		
		if( $value['_loading'] == 'require_once' )
			require_once "$IP/extensions/$extension/$extension.php";
	}
}

