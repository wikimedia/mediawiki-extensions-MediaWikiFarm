<?php

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
	 * @return MediaWikiFarm Singleton.
	 */
	static function initialise() {
		
		global $wgMediaWikiFarmConfigDir;
		
		if( self::$self == null )
			self::$self = new self( $GLOBALS['_SERVER']['HTTP_HOST'], $wgMediaWikiFarmConfigDir );
		
		return self::$self;
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
		
		$this->configDir = isset( $configDir ) && is_string( $configDir ) ? $configDir : '/etc/mediawiki';
		
		$config = array();
		if( is_file( $this->configDir . '/farms.yml' ) ) {
			
			try {
				
				$config = \Symphony\Component\Yaml\Yaml::parse( file_get_contents( $this->configDir . '/farms.yml' ) );
			}
			catch( \Symfony\Component\Yaml\Exception\ParseException $e ) {
				
				$this->unusable = true;
			}
		}
		else if( is_file( $this->configDir . '/farms.php' ) )
			$config = require $this->configDir . '/farms.php';
		
		else
			$this->unusable = true;
		
		# Now select the right configuration amoung all farms
		if( !$this->unusable )
			$this->unusable = !$this->selectFarm( $config );
	}
	
	/**
	 * Select the farm.
	 * 
	 * @param array $configs All farm configurations.
	 * @param string $host Requested host.
	 * return bool One of the farm has been selected.
	 */
	private function selectFarm( $configs, $host ) {
		
		foreach( $configs as $regex => $config ) {
			
			if( preg_match( '/' . $regex . '/', $matches ) ) {
				
				$this->config = $config;
				$this->variables = $matches;
				return true;
			}
		}
		
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


// Load skins with the require_once mechanism
function MediaWikiFarm_loadSkinsConfig( $skins ) {
	
	foreach( $skins as $skin => $value ) {
		
		if( $value['_loading'] == 'require_once' )
			require_once "$IP/skins/$skin/$skin.php";
	}
}

// Load extensions with the require_once mechanism
function MediaWikiFarm_loadExtensionsConfig( $extensions ) {
	
	foreach( $extensions as $extension => $value ) {
		
		if( $value['_loading'] == 'require_once' )
			require_once "$IP/extensions/$extension/$extension.php";
	}
}

