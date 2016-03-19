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

@include_once __DIR__ . '/../vendor/autoload.php';

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
	
	/** @var array [private] Variables inside the host. */
	public $variables = array();
	
	/** @var array Selected wiki. */
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
		
		$this->setWgConf();
		
		return true;
	}
	
	/**
	 * Computation of the suffix and wikiID.
	 * 
	 * This function is the central point to get the unique identifier of the wiki, wikiID.
	 * 
	 * @return bool The wikiID and suffix were set, and the wiki could exist.
	 */
	function setWikiID() {
		
		if( $this->unusable )
			return false;
		
		$this->wiki = $this->config;
		$this->wiki['version'] = null;
		$this->wiki['globals'] = null;
		
		$this->setWikiProperty( 'suffix', false, true, true );
		$this->variables['suffix'] = $this->wiki['suffix'];
		
		$this->setWikiProperty( 'wikiID', false, false, true );
		$this->variables['wikiID'] = $this->wiki['wikiID'];
		
		if( !array_key_exists( 'wikiID', $this->wiki ) ) {
			$this->unusable = true;
			return false;
		}
		
		if( !$this->wiki['wikiID'] )
			return false;
		
		return true;
	}
	
	/**
	 * Setting of the version, either from the input if already got, either from a file.
	 * 
	 * @param string|null $version If a string, this is the version already got, just set it.
	 * @return bool The version was set, and the wiki could exist.
	 */
	function setVersion( $version = null ) {
		
		global $IP, $wgVersion;
		
		if( $this->unusable )
			return false;
		
		$this->setWikiProperty( 'versions' );
		
		# In the case multiversion is configured and version is already known
		if( is_string( $version ) && is_string( $this->codeDir ) && is_file( $this->codeDir . '/' . $version . '/includes/DefaultSettings.php' ) )
			$this->wiki['code'] = $this->codeDir . '/' . $version;
		
		# In the case multiversion is configured, but version is not known as of now
		else if( is_null( $version ) && is_string( $this->codeDir ) ) {
			
			$versions = $this->readFile( $this->config['versions'] );
			
			if( !$versions ) {
				$this->unusable = true;
				return false;
			}
			
			if( array_key_exists( $this->wiki['wikiID'], $versions ) && is_file( $this->codeDir . '/' . $versions[$this->wiki['wikiID']] . '/includes/DefaultSettings.php' ) )
				$version = $versions[$this->wiki['wikiID']];
			
			else if( $this->wiki['suffix'] && array_key_exists( $this->wiki['suffix'], $versions ) && is_file( $this->codeDir . '/' . $versions[$this->wiki['suffix']] . '/includes/DefaultSettings.php' ) )
				$version = $versions[$this->wiki['suffix']];
			
			else if( array_key_exists( 'default', $versions ) && is_file( $this->codeDir . '/' . $versions['default'] . '/includes/DefaultSettings.php' ) )
				$version = $versions['default'];
			
			else return false;
			
			$this->wiki['code'] = $this->codeDir . '/' . $version;
		}
		
		# In the case no multiversion is configured
		else if( is_null( $this->codeDir ) ) {
			
			$version = $wgVersion;
			$this->wiki['code'] = $IP;
		}
		else {
			$this->unusable = true;
			return false;
		}
		
		# Set the version in the wiki configuration and as a variable to be used later
		$this->variables['version'] = $version;
		$this->wiki['version'] = $version;
		
		return true;
	}
	
	/**
	 * Computation of the properties, which could depend on the suffix, wikiID, or other variables.
	 * 
	 * @return bool The wiki properties were set, and the wiki could exist.
	 */
	function setWikiProperties() {
		
		if( $this->unusable )
			return false;
		
		$this->setWikiProperty( 'data', false );
		$this->setWikiProperty( 'cache', false );
		$this->setWikiProperty( 'config', true );
		$this->setWikiProperty( 'post-config', true );
		$this->setWikiProperty( 'exec-config', true );
		
		foreach( $this->wiki['variables'] as &$variable ) {
			
			$this->setWikiPropertyValue( $variable['file'], false );
			$this->setWikiPropertyValue( $variable['config'], true );
			$this->setWikiPropertyValue( $variable['post-config'], true );
			$this->setWikiPropertyValue( $variable['exec-config'], true );
		}
		
		return true;
	}
	
	function setWgConf() {
		
		global $wgConf;
		
		// TODO Still hacky: before setting parameters in stone in farms.yml, various configurations should be reviewed to select accordingly the rights management modelisation
		$wgConf->suffixes = array( $this->wiki['suffix'] );
		$wikiIDs = $this->readFile( $this->configDir . '/' . $this->wiki['suffix'] . '/wikis.yml' );
		foreach( $wikiIDs as $wiki => $value ) {
			$wgConf->wikis[] = $wiki . '-' . $this->wiki['suffix'];
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
		else if( $configs = $this->readFile( $this->configDir . '/farms.json' ) );
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
	
	/**
	 * Replacement of the variables in the host name.
	 * 
	 * @return string|null|false If an existing version is found in files, returns a string; if no version is found, returns null; if the host is missing in existence files, returns false; if an existence file is missing or badly formatted, return false and turns this object into a unusable state.
	 */
	private function replaceHostVariables() {
		
		$version = null;
		
		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->config['variables'] as $variable ) {
			
			$key = $variable['variable'];
			
			# If the variable doesn’t exist, continue
			if( !array_key_exists( $key, $this->variables ) )
				continue;
			$value = $this->variables[$key];
			
			# If every values are correct, continue
			if( !array_key_exists( 'file', $variable ) )
				continue;
			$filename = $variable['file'];
			
			# Really check if the variable is in the listing file
			$this->setWikiPropertyValue( $filename, false, false, true );
			$choices = $this->readFile( $this->configDir . '/' . $filename );
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
				
				if( isset( $this->codeDir ) && is_dir( $this->codeDir . '/' . ((string) $choices[$value]) ) && is_file( $this->codeDir . '/' . ((string) $choices[$value]) . '/includes/DefaultSettings.php' ) )
					$version = (string) $choices[$value];
			}
		}
		
		return $version;
	}
	
	
	
	/*
	 * Helper Methods (public)
	 * ----------------------- */
	
	/**
	 * Read a file either in PHP, YAML (if library available), JSON, or dblist, and returns the interpreted array.
	 * 
	 * The choice between the format depends on the extension: php, yml, yaml, json, dblist.
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
		
		if( $format == 'php' ) {
			
			$array = @include $filename;
			
			if( !is_array( $array ) )
				return false;
			
			return $array;
		}
		
		if( $format == 'yml' || $format == 'yaml' ) {
			
			if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) )
				return false;
			
			try {
				
				$array = Symfony\Component\Yaml\Yaml::parse( file_get_contents( $filename ) );
				if( !is_array( $array ) )
					return false;
				
				return $array;
			}
			catch( Symfony\Component\Yaml\Exception\ParseException $e ) {
				
				return false;
			}
		}
		
		if( $format == 'json' ) {
			
			$array = json_decode( file_get_contents( $filename ), true );
			if( !is_array( $array ) )
				return false;
			
			return $array;
		}
		
		if( $format == 'dblist' ) {
			
			$content = file_get_contents( $filename );
			
			if( !$content )
				return array();
			
			return explode( "\n", $content );
		}
		
		return false;
	}
	
	/**
	 * Set a wiki property and replace placeholders (property name version).
	 * 
	 * @param string $name Name of the property.
	 * @param bool $toArray Change a string to an array with the string.
	 * @param bool $create Create the property the empty string if non-existent.
	 * @param bool $reset Empty the variables internal cache after operation.
	 * @return void
	 */
	private function setWikiProperty( $name, $toArray = false, $create = false, $reset = false ) {
		
		if( !array_key_exists( $name, $this->wiki ) ) {
			
			if( $create ) $this->wiki[$name] = '';
			else return;
		}
		
		$this->setWikiPropertyValue( $this->wiki[$name], $toArray, $create, $reset );
	}
	
	/**
	 * Set a wiki property and replace placeholders (value version).
	 * 
	 * @param string|null $value Value of the property.
	 * @param bool $toArray Change a string to an array with the string.
	 * @param bool $create Create the property the empty string if non-existent.
	 * @param bool $reset Empty the variables internal cache after operation.
	 * @return void
	 */
	private function setWikiPropertyValue( &$value, $toArray = false, $create = false, $reset = false ) {
		
		static $rkeys = array(), $rvalues = array();
		if( count( $rkeys ) == 0 ) {
			
			$rvalues = array();
			foreach( $this->variables as $key => $val ) {
				$rkeys[] = '/\$' . preg_quote( $key, '/' ) . '/';
				$rvalues[] = $val;
			}
		}
		
		if( is_null( $value ) )
			return;
		else if( is_string( $value ) ) {
			
			if( $toArray ) $value = array( $value );
			else $value = preg_replace( $rkeys, $rvalues, $value );
		}
		else {
			
			$this->unusable = true;
			return;
		}
		
		if( $toArray ) {
			
			foreach( $value as &$subvalue )
				$subvalue = preg_replace( $rkeys, $rvalues, $subvalue );
		}
		
		if( $reset )
			$rkeys = array();
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
	function getMediaWikiConfig() {
		
		global $wgConf;
		
		if( $this->unusable )
			return false;
		
		$myWiki = $this->wiki['wikiID'];
		$mySuffix = $this->wiki['suffix'];
		
		$codeDir = $this->wiki['code'];
		$cacheFile = $this->wiki['cache'];
		$generalYamlFilename = '/'.$this->wiki['config'][0];
		$suffixedYamlFilename = '/'.$this->wiki['variables'][0]['config'][0];
		$privateYamlFilename = '/'.$this->wiki['post-config'][0];
		
		//var_dump($wgConf);
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
		                                     filemtime( $this->configDir.$privateYamlFilename ) )
		    && is_string( $cacheFile ) )
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
			$globals['general'] = $wgConf->getAll( $myWiki, $mySuffix );
			
			// For the permissions array, fix a small strangeness: when an existing default permission
			// is true, it is not possible to make it false in the specific configuration
			if( array_key_exists( '+wgGroupPermissions', $wgConf->settings ) )
				
				$globals['general']['wgGroupPermissions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgGroupPermissions', $myWiki, $mySuffix ), $globals['general']['wgGroupPermissions'] );
			
			//if( array_key_exists( '+wgDefaultUserOptions', $wgConf->settings ) )
				//$globals['general']['wgDefaultUserOptions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgDefaultUserOptions', $myWiki, $mySuffix ), $globals['general']['wgDefaultUserOptions'] );
			
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
			
			// Register this extension MediaWikiFarm to appear in Special:Version
			$globals['extensions']['MediaWikiFarm']['_loading'] = 'wfLoadExtension';
			
			// Save this configuration in a serialised file
			if( is_string( $cacheFile ) ) {
				@mkdir( dirname( $cacheFile ) );
				$tmpFile = tempnam( dirname( $cacheFile ), basename( $cacheFile ).'.tmp' );
				chmod( $tmpFile, 0640 );
				if( $tmpFile && file_put_contents( $tmpFile, serialize( $globals ) ) ) {
					rename( $tmpFile, $cacheFile );
				}
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
		
		if( !is_array( $this->wiki ) && array_key_exists( 'globals', $this->wiki ) ) {
			$this->unusable = true;
			return;
		}
		
		if( !is_array( $this->wiki['globals'] ) )
			$this->getMediaWikiConfig();
		
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

