<?php
/**
 * Class MediaWikiFarmConfiguration.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */


/**
 * Class dedicated to configuration compilation.
 */
class MediaWikiFarmConfiguration {

	/*
	 * Properties
	 * ---------- */

	/** @var MediaWikiFarm|null Main object. */
	protected $farm = null;

	/** @var array Environment. */
	protected $environment = array(
		'ExtensionRegistry' => null,
	);

	/** @var array Configuration parameters for this wiki. */
	protected $configuration = array(
		'settings' => array(),
		'arrays' => array(),
		'extensions' => array(),
		'execFiles' => array(),
		'composer' => array(),
	);



	/*
	 * Functions of interest in normal operations
	 * ------------------------------------------ */

	/**
	 * Construction.
	 *
	 * @param MediaWikiFarm $farm Main object.
	 * @return MediaWikiFarmConfiguration
	 */
	public function __construct( &$farm ) {

		$this->farm = $farm;
	}

	/**
	 * Get MediaWiki configuration.
	 *
	 * This associative array contains four sections:
	 *   - 'settings': associative array of MediaWiki configuration (e.g. 'wgServer' => '//example.org');
	 *   - 'arrays': associative array of MediaWiki configuration of type array (e.g. 'wgGroupPermissions' => array( 'edit' => false ));
	 *   - 'extensions': list of extensions and skins (e.g. 0 => array( 'ParserFunctions', 'extension', 'wfLoadExtension' ));
	 *   - 'composer': list of Composer-installed extensions and skins (e.g. 0 => 'ExtensionSemanticMediaWiki');
	 *   - 'execFiles': list of PHP files to execute at the end.
	 *
	 * @api
	 * @mediawikifarm-const
	 *
	 * @param string|false $key Key of the wanted section or false for the whole array.
	 * @param string|false $key2 Subkey (specific to each entry) or false for the whole entry.
	 * @return array MediaWiki configuration, either entire, either a part depending on the parameter.
	 */
	public function getConfiguration( $key = false, $key2 = false ) {
		if( $key !== false ) {
			if( array_key_exists( $key, $this->configuration ) ) {
				if( $key2 !== false && array_key_exists( $key2, $this->configuration[$key] ) ) {
					return $this->configuration[$key][$key2];
				} elseif( $key2 !== false ) {
					return null;
				}
				return $this->configuration[$key];
			}
			return null;
		}
		return $this->configuration;
	}

	/**
	 * Set the 'composer' key in the configuration.
	 *
	 * @param string[] $composer List of Composer-installed extensions.
	 * @return void
	 */
	public function setComposer( $composer ) {

		$this->configuration['composer'] = $composer;
	}

	/**
	 * Popuplate the settings array directly from config files (without wgConf).
	 *
	 * There should be no major differences is results between this function and results
	 * of SiteConfiguration::getAll(), but probably some edge cases. At the contrary of
	 * Siteconfiguration, this implementation is focused on performance for current wiki:
	 * only the parameters for current wikis are issued, contrary to wgConf’s strategy
	 * map-all-and-reduce. An additional loop over config files is here; wgConf delegates
	 * this externally if wanted.
	 *
	 * The priories used here are implicit in wgConf but exist and behave similarly:
	 * 0 = default value from MW; 1 = explicit very default value from a specific file;
	 * 2 = standard default value; 3 = value with suffix-priority;
	 * 4 = value with tag-priority; 5 = value with specific-wiki-priority. Files are
	 * processed in-order and linearly; for a given setting, only values with a greater
	 * or equal priority can override a previous value.
	 *
	 * There are two resulting arrays in the object property array 'configuration':
	 *   - settings: scalar (or array) values overriding the default MW values;
	 *   - arrays: array values merged into the default MW values.
	 * They are processed differently given their different nature, and to facilitate
	 * mass-import into global scope (or other configuration object). Another minor
	 * reason is: if this processing is done on a MediaWiki installation in a version
	 * different from the target version (sort of cross-compilation), the compiling
	 * MW is not aware of the default array value of the target MW, so it is
	 * safer to only manipulate the known difference.
	 *
	 * @internal
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return bool Success.
	 */
	public function populateSettings() {

		$settings = &$this->configuration['settings'];
		$priorities = array();
		$settingsArray = &$this->configuration['arrays'];
		$prioritiesArray = array();

		$extensions =& $this->configuration['extensions'];

		$settings['wgUseExtensionMediaWikiFarm'] = true;
		$extensions['ExtensionMediaWikiFarm'] = array( 'MediaWikiFarm', 'extension', null, 0 );

		$farmConfig = $this->farm->getFarmConfiguration();

		foreach( $farmConfig['config'] as $configFile ) {

			if( !is_array( $configFile ) ) {
				continue;
			}

			# Replace variables
			$configFile = $this->farm->replaceVariables( $configFile );

			# Executable config files
			if( array_key_exists( 'executable', $configFile ) && $configFile['executable'] ) {

				$this->configuration['execFiles'][] = $this->farm->getConfigDir() . '/' . $configFile['file'];
				continue;
			}

			$theseSettings = $this->farm->readFile( $configFile['file'], $this->farm->getConfigDir() );
			if( $theseSettings === false ) {
				# If a file is unavailable, skip it
				continue;
			}

			# Defined key
			if( strpos( $configFile['key'], '*' ) === false ) {

				$priority = 0;
				if( $configFile['key'] == 'default' ) {
					$priority = 1;
				} elseif( $configFile['key'] == $this->farm->getVariable( '$SUFFIX' ) ) {
					$priority = 3;
				} elseif( $configFile['key'] == $this->farm->getVariable( '$WIKIID' ) ) {
					$priority = 5;
				} else {
					/*foreach( $tags as $tag ) {
						if( $configFile['key'] == $tag ) {
							$priority = 4;
							break;
						}
					}*/
					if( $priority == 0 ) {
						continue;
					}
				}

				foreach( $theseSettings as $rawSetting => $value ) {

					# Sanitise the setting name
					$setting = preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $rawSetting );

					if( substr( $rawSetting, 0, 1 ) == '+' ) {
						if( !array_key_exists( $setting, $prioritiesArray ) || $prioritiesArray[$setting] <= $priority ) {
							$settingsArray[$setting] = $value;
							$prioritiesArray[$setting] = $priority;
						}
					}
					elseif( !array_key_exists( $setting, $priorities ) || $priorities[$setting] <= $priority ) {
						$settings[$setting] = $value;
						$priorities[$setting] = $priority;
						if( substr( $setting, 0, 14 ) == 'wgUseExtension' ) {
							$extensions['Extension' . substr( $rawSetting, 14 )] = array( substr( $rawSetting, 14 ), 'extension', null, count( $extensions ) );
						} elseif( substr( $setting, 0, 9 ) == 'wgUseSkin' ) {
							$extensions['Skin' . substr( $rawSetting, 9 )] = array( substr( $rawSetting, 9 ), 'skin', null, count( $extensions ) );
						}
					}
				}
			}

			# Regex key
			else {

				// $tags = array(); # @todo data sources not implemented, but code to selection parameters from a tag is below

				$defaultKey = '';
				$classicKey = '';
				if( array_key_exists( 'default', $configFile ) && is_string( $configFile['default'] ) ) {
					$defaultKey = $this->farm->replaceVariables( $configFile['default'] );
				}
				if( is_string( $configFile['key'] ) ) {
					$classicKey = $this->farm->replaceVariables( $configFile['key'] );
				}

				# These are precomputations of the condition `$classicKey == $wikiID` (is current wiki equal to key indicated in config file?)
				# to avoid recompute it each time in the loop. This is a bit more complex to take into account the star: $wikiID is the part
				# corresponding to the star from the variable $WIKIID if $classicKey can match $WIKIID when remplacing the star by something
				# (the star will be the key in the files). This reasonning is “inversed” compared to a loop checking each key in the files
				# in order to use array_key_exists, assumed to be quicker than a direct loop.
				$wikiIDKey = (bool) preg_match( '/^' . str_replace( '*', '(.+)', $classicKey ) . '$/', $this->farm->getVariable( '$WIKIID' ), $matches );
				$wikiID = $wikiIDKey ? $matches[1] : $this->farm->getVariable( '$WIKIID' );
				$suffixKey = (bool) preg_match( '/^' . str_replace( '*', '(.+)', $classicKey ) . '$/', $this->farm->getVariable( '$SUFFIX' ), $matches );
				$suffix = $suffixKey ? $matches[1] : $this->farm->getVariable( '$SUFFIX' );
				/*$tagKey = array();
				foreach( $tags as $tag ) {
					$tagKey[$tag] = ($classicKey == $tag);
				}*/
				if( $defaultKey ) {
					$suffixDefaultKey = (bool) preg_match( '/^' . str_replace( '*', '(.+)', $defaultKey ) . '$/', $this->farm->getVariable( '$SUFFIX' ), $matches );
					// $tagDefaultKey = in_array( $defaultKey, $tags );
				}

				foreach( $theseSettings as $rawSetting => $values ) {

					# Sanitise the setting name
					$setting = preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $rawSetting );

					# Depending if it is an array diff or not, create and initialise the variables
					if( substr( $rawSetting, 0, 1 ) == '+' ) {
						$settingIsArray = true;
						if( !array_key_exists( $setting, $prioritiesArray ) ) {
							$settingsArray[$setting] = array();
							$prioritiesArray[$setting] = 0;
						}
						$thisSetting = &$settingsArray[$setting];
						$thisPriority = &$prioritiesArray[$setting];
					} else {
						$settingIsArray = false;
						if( !array_key_exists( $setting, $priorities ) ) {
							$settings[$setting] = null;
							$priorities[$setting] = 0;
						}
						$thisSetting = &$settings[$setting];
						$thisPriority = &$priorities[$setting];
						if( substr( $setting, 0, 14 ) == 'wgUseExtension' ) {
							$extensions['Extension' . substr( $rawSetting, 14 )] = array( substr( $rawSetting, 14 ), 'extension', null, count( $extensions ) );
						} elseif( substr( $setting, 0, 9 ) == 'wgUseSkin' ) {
							$extensions['Skin' . substr( $rawSetting, 9 )] = array( substr( $rawSetting, 9 ), 'skin', null, count( $extensions ) );
						}
					}

					# Set value if there is a label corresponding to wikiID
					if( $wikiIDKey ) {
						if( array_key_exists( $wikiID, $values ) ) {
							$thisSetting = $values[$wikiID];
							$thisPriority = 5;
							continue;
						}
						if( array_key_exists( '+' . $wikiID, $values ) && is_array( $values['+' . $wikiID] ) ) {
							$thisSetting = MediaWikiFarmUtils::arrayMerge( $thisSetting, $values['+' . $wikiID] );
							$thisPriority = 3;
						}
					}

					# Set value if there are labels corresponding to given tags
					/*$setted = false;
					foreach( $tags as $tag ) {
						if( $tagKey[$tag] && $thisPriority <= 4 ) {
							if( array_key_exists( $tag, $values ) ) {
								$thisSetting = $value[$tag];
								$thisPriority = 4;
								$setted = true;
								# NB: for strict equivalence with wgConf there should be here a `break`, but by consistency
								# (last value kept) and given the case should not appear, there is no.
							}
							elseif( array_key_exists( '+'.$tag, $values ) && is_array( $values['+'.$tag] ) ) {
								$thisSetting = MediaWikiFarmUtils::arrayMerge( $thisSetting, $values['+'.$tag] );
								$thisPriority = 3;
							}
						}
					}
					if( $setted ) {
						continue;
					}*/

					# Set value if there is a label corresponding to suffix
					if( $suffixKey && $thisPriority <= 3 ) {
						if( array_key_exists( $suffix, $values ) ) {
							$thisSetting = $values[$suffix];
							$thisPriority = 3;
							continue;
						}
						if( array_key_exists( '+' . $suffix, $values ) && is_array( $values['+' . $suffix] ) ) {
							$thisSetting = MediaWikiFarmUtils::arrayMerge( $thisSetting, $values['+' . $suffix] );
							$thisPriority = 3;
						}
					}

					# Default value
					if( $thisPriority <= 2 && array_key_exists( 'default', $values ) ) {
						$thisSetting = $values['default'];
						$thisPriority = 2;
						if( $defaultKey ) {
							if( $suffixDefaultKey ) {
								$thisPriority = 3;
							/*} elseif( $tagDefaultKey ) {
								$thisPriority = 4;*/
							}
						}
						continue;
					}

					# Nothing was selected, clean up
					if( $thisPriority == 0 ) {
						if( $settingIsArray ) {
							unset( $settingsArray[$setting] );
							unset( $prioritiesArray[$setting] );
						} else {
							unset( $settings[$setting] );
							unset( $priorities[$setting] );
							if( substr( $setting, 0, 14 ) == 'wgUseExtension' ) {
								unset( $extensions['Extension' . substr( $rawSetting, 14 )] );
							} elseif( substr( $setting, 0, 9 ) == 'wgUseSkin' ) {
								unset( $extensions['Skin' . substr( $rawSetting, 9 )] );
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Set environment, i.e. every 'environment variables' which lead to a known configuration.
	 *
	 * For now, the only environment variable is ExtensionRegistry (is the MediaWiki version
	 * capable of loading extensions/skins with wfLoadExtension/wfLoadSkin?).
	 *
	 * @internal
	 *
	 * @param string|null $key Set the keyed environment variable or all environment variables.
	 * @param mixed|null $value Value of the keyed environment variable.
	 * @return void
	 */
	public function setEnvironment( $key = null, $value = null ) {

		if( $key === null ) {
			$key = 'ExtensionRegistry';
		}

		# Set environment
		if( $key == 'ExtensionRegistry' ) {
			if( $value === null ) {
				$value = class_exists( 'ExtensionRegistry' );
			}
			$this->environment['ExtensionRegistry'] = $value;
		}
	}

	/**
	 * Activate extensions and skins depending on their autoloading and activation mechanisms.
	 *
	 * When the environment parameter ExtensionRegistry is not set (null), only Composer-enabled
	 * extensions and skins are Composer-autoloaded; and if ExtensionRegistry is set to true or
	 * false, extensions and skins are activated through wfLoadExtension/wfLoadSkin or require_once.
	 *
	 * The part related to Composer is a bit complicated (partly since optimised): when an extension
	 * is Composer-managed, it is checked if it was already loaded by another extension (in which
	 * case Composer has autoloaded its code), then it is registered as Composer-managed, then its
	 * required extensions are registered. This last part is important, else Composer would have
	 * silently autoloaded the required extensions, but these would not be known by MediaWikiFarm
	 * and, more importantly, an eventual wfLoadExtension would not be triggered (e.g. PageForms 4.0+
	 * is a Composer dependency of the Composer-installed SemanticFormsSelect; if you activate SFS
	 * but not PF, PF would not be wfLoadExtension’ed – since unknown from MediaWikiFarm – and the
	 * wfLoadExtension’s SFS issues a fatal error since PF is not wfLoadExtension’ed, even if it is
	 * Composer-installed).
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function activateExtensions() {

		# Autodetect if ExtensionRegistry is here
		$ExtensionRegistry = $this->environment['ExtensionRegistry'];

		# Load Composer dependencies if available
		$composerLoaded = array();
		$dependencies = $this->farm->readFile( 'MediaWikiExtensions.php', $this->farm->getVariable( '$CODE' ) . '/vendor', false );
		if( !$dependencies ) {
			$dependencies = array();
		}

		# Search for skin and extension activation
		foreach( $this->configuration['extensions'] as $key => &$extension ) {

			$type = $extension[1];
			$name = $extension[0];
			$status =& $extension[2];

			$setting = 'wgUse' . preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $key );
			$value =& $this->configuration['settings'][$setting];

			# Extension is deactivated
			if( $value === false ) {
				$status = null;
				unset( $this->configuration['extensions'][$key] );

			# Mechanism Composer wanted
			} elseif( ( $value === 'composer' || $value === true ) && $this->detectComposer( $type, $name ) ) {
				$status = 'composer';
				$value = true;

			# MediaWiki still not loaded: we must wait before taking a decision
			} elseif( $ExtensionRegistry === null ) {
				# nop

			# Mechanism require_once wanted
			} elseif( $value === 'require_once' && $this->detectLoadingMechanism( $type, $name, true ) == $value ) {
				$status = $value;
				$value = true;

			# Mechanism wfLoadSkin/wfLoadExtension wanted
			} elseif( $value === 'wfLoad' . ucfirst( $type ) && $this->detectLoadingMechanism( $type, $name ) == $value ) {
				$status = $value;
				$value = true;

			# Any mechanism to load the extension
			// @codingStandardsIgnoreLine MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
			} elseif( $value === true && $status = $this->detectLoadingMechanism( $type, $name ) ) {
				# nop

			# Missing extension or wrong configuration value
			} elseif( $key != 'ExtensionMediaWikiFarm' ) {
				$this->farm->log[] = "Requested but missing $type $name for wiki " .
					$this->farm->getVariable( '$WIKIID' ) . ' in version ' .
					$this->farm->getVariable( '$VERSION' );
				$value = false;
				$status = null;
				unset( $this->configuration['extensions'][$key] );

			# MediaWikiFarm is specific because in a non-standard location
			} else {
				$status = $ExtensionRegistry ? 'wfLoadExtension' : 'require_once';
			}

			if( $status == 'composer' ) {
				if( in_array( $key, $composerLoaded ) ) {
					continue;
				}
				$this->configuration['composer'][] = $key;
				if( array_key_exists( $key, $dependencies ) ) {
					$composerLoaded = array_merge( $composerLoaded, $dependencies[$key] );
					foreach( $dependencies[$key] as $dep ) {
						if( !array_key_exists( $dep, $this->configuration['extensions'] ) ) {
							$this->configuration['settings']['wgUse' . preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $dep )] = true;
							preg_match( '/^(Extension|Skin)(.+)$/', $dep, $matches );
							$this->configuration['extensions'][$dep] = array( $matches[2], strtolower( $matches[1] ), 'composer', - count( $this->configuration['extensions'] ) );
						} else {
							$this->configuration['extensions'][$dep][2] = 'composer';
							$this->configuration['extensions'][$dep][3] = - abs( $this->configuration['extensions'][$dep][3] );
						}
					}
				}
			}
		}

		# Sort extensions
		uasort( $this->configuration['extensions'], array( 'MediaWikiFarmConfiguration', 'sortExtensions' ) );
		$i = 0;
		foreach( $this->configuration['extensions'] as $key => &$extension ) {
			$extension[3] = $i++;
		}
	}

	/**
	 * Detect if the extension can be loaded by Composer.
	 *
	 * This use the backend-generated key; without it, no extension can be loaded with Composer in MediaWikiFarm.
	 *
	 * @internal
	 * @mediawikifarm-const
	 *
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @return bool The extension/skin is Composer-managed (at least for its installation).
	 */
	public function detectComposer( $type, $name ) {

		if( is_file( $this->farm->getVariable( '$CODE' ) . '/' . $type . 's/' . $name . '/composer.json' ) &&
		    is_dir( $this->farm->getVariable( '$CODE' ) . '/vendor/composer' . self::composerKey( ucfirst( $type ) . $name ) ) ) {

			return true;
		}
		return false;
	}

	/**
	 * Detection of the loading mechanism of extensions and skins.
	 *
	 * @internal
	 * @mediawikifarm-const
	 *
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @param bool $preferedRO Prefered require_once mechanism.
	 * @return string|null Loading mechnism in ['wfLoadExtension', 'wfLoadSkin', 'require_once'] or null if all mechanisms failed.
	 */
	public function detectLoadingMechanism( $type, $name, $preferedRO = false ) {

		# Search base directory
		$base = $this->farm->getVariable( '$CODE' ) . '/' . $type . 's';
		if( $type == 'extension' && array_key_exists( 'wgExtensionDirectory', $this->configuration['settings'] ) ) {
			$base = $this->configuration['settings']['wgExtensionDirectory'];
		}
		elseif( $type == 'skin' && array_key_exists( 'wgStyleDirectory', $this->configuration['settings'] ) ) {
			$base = $this->configuration['settings']['wgStyleDirectory'];
		}

		if( !is_dir( $base . '/' . $name ) ) {
			return null;
		}

		# A MyExtension.php file is in the directory -> assume it is the loading mechanism
		if( $preferedRO === true && is_file( $base . '/' . $name . '/' . $name . '.php' ) ) {
			return 'require_once';
		}

		# An extension.json/skin.json file is in the directory -> assume it is the loading mechanism
		if( $this->environment['ExtensionRegistry'] && is_file( $base . '/' . $name . '/' . $type . '.json' ) ) {
			return 'wfLoad' . ucfirst( $type );
		}

		# A MyExtension.php file is in the directory -> assume it is the loading mechanism
		elseif( is_file( $base . '/' . $name . '/' . $name . '.php' ) ) {
			return 'require_once';
		}

		return null;
	}

	/**
	 * Sort extensions.
	 *
	 * The extensions are sorted first by loading mechanism, then, for Composer-managed
	 * extensions, according to their dependency order.
	 *
	 * @internal
	 *
	 * @param array $a First element.
	 * @param array $b Second element.
	 * @return int Relative order of the two elements.
	 */
	public function sortExtensions( $a, $b ) {

		static $loading = array(
			'' => 0,
			'composer' => 10,
			'require_once' => 20,
			'wfLoadSkin' => 30,
			'wfLoadExtension' => 30,
		);
		static $type = array(
			'skin' => 1,
			'extension' => 2,
		);

		$loadA = $a[2] === null ? '' : $a[2];
		$loadB = $b[2] === null ? '' : $b[2];
		$weight = $loading[$loadA] + $type[$a[1]] - $loading[$loadB] - $type[$b[1]];
		$stability = $a[3] - $b[3];

		if( $a[2] == 'composer' && $b[2] == 'composer' ) {
			# Read the two composer.json, if one is in the require section, it must be before
			$nameA = ucfirst( $a[1] ) . $a[0];
			$nameB = ucfirst( $b[1] ) . $b[0];
			$dependencies = $this->farm->readFile( 'MediaWikiExtensions.php', $this->farm->getVariable( '$CODE' ) . '/vendor', false );
			if( !$dependencies || !array_key_exists( $nameA, $dependencies ) || !array_key_exists( $nameB, $dependencies ) ) {
				return $weight ? $weight : $stability;
			}
			$ArequiresB = in_array( $nameB, $dependencies[$nameA] );
			$BrequiresA = in_array( $nameA, $dependencies[$nameB] );
			if( $ArequiresB && $BrequiresA ) {
				return $stability;
			} elseif( $BrequiresA ) {
				return -1;
			} elseif( $ArequiresB ) {
				return 1;
			}
		}

		return $weight ? $weight : $stability;
	}

	/**
	 * Create a LocalSettings.php.
	 *
	 * A previous mechanism tested in this extension was to load each category of
	 * parameters separately (general settings, arrays, skins, extensions) given the
	 * cached file [cache]/[farm]/config-VERSION-SUFFIX-WIKIID.php, but comparison with
	 * a classical LocalSettings.php was proven to be quicker. Additionally debug will
	 * be easier since a LocalSettings.php is easier to read than a 2D array.
	 *
	 * @internal
	 *
	 * @param array $configuration Array with the schema defined for $this->configuration.
	 * @param bool $isMonoversion Is MediaWikiFarm configured for monoversion?
	 * @param string $preconfig PHP code to be added at the top of the file.
	 * @param string $postconfig PHP code to be added at the end of the file.
	 * @return string Content of the file LocalSettings.php.
	 */
	public static function createLocalSettings( $configuration, $isMonoversion, $preconfig = '', $postconfig = '' ) {

		# Prepare paths
		$extDir = $GLOBALS['IP'] . '/extensions';
		$path = array(
			'extension' => '$IP/extensions',
			'skin' => '$IP/skins',
		);
		if( array_key_exists( 'wgExtensionDirectory', $configuration['settings'] ) ) {
			$path['extension'] = $configuration['settings']['wgExtensionDirectory'];
			$extDir = $path['extension'];
		}
		if( array_key_exists( 'wgStyleDirectory', $configuration['settings'] ) ) {
			$path['skin'] = $configuration['settings']['wgStyleDirectory'];
		}

		$localSettings = "<?php\n";

		if( $preconfig ) {
			$localSettings .= "\n" . $preconfig;
		}

		# Sort extensions and skins by loading mechanism
		$extensions = array(
			'extension' => array(
				'require_once' => '',
				'wfLoadExtension' => '',
			),
			'skin' => array(
				'require_once' => '',
				'wfLoadSkin' => '',
			),
		);
		foreach( $configuration['extensions'] as $key => $extension ) {
			if( $extension[2] == 'require_once' && $key != 'ExtensionMediaWikiFarm' ) {
				$extensions[$extension[1]]['require_once'] .= "require_once \"{$path[$extension[1]]}/{$extension[0]}/{$extension[0]}.php\";\n";
			} elseif( $extension[2] == 'wfLoad' . ucfirst( $extension[1] ) ) {
				if( $key != 'ExtensionMediaWikiFarm' || ( $key == 'ExtensionMediaWikiFarm' && "$extDir/MediaWikiFarm" == dirname( dirname( __FILE__ ) ) ) ) {
					$extensions[$extension[1]]['wfLoad' . ucfirst( $extension[1] )] .= 'wfLoad' . ucfirst( $extension[1] ) . '( ' .
						var_export( $extension[0], true ) . " );\n";
				} else {
					$extensions['extension']['wfLoadExtension'] .= "wfLoadExtension( 'MediaWikiFarm', " .
						var_export( dirname( dirname( __FILE__ ) ) . '/extension.json', true ) . " );\n";
				}
			}
		}

		# Skins loaded with require_once
		if( $extensions['skin']['require_once'] ) {
			$localSettings .= "\n# Skins loaded with require_once\n";
			$localSettings .= $extensions['skin']['require_once'];
		}

		# Extensions loaded with require_once
		if( $extensions['extension']['require_once'] ) {
			$localSettings .= "\n# Extensions loaded with require_once\n";
			$localSettings .= $extensions['extension']['require_once'];
		}

		# General settings
		$localSettings .= "\n# General settings\n";
		foreach( $configuration['settings'] as $setting => $value ) {
			$localSettings .= "\$$setting = " . var_export( $value, true ) . ";\n";
		}

		# Array settings
		$localSettings .= "\n# Array settings\n";
		foreach( $configuration['arrays'] as $setting => $value ) {
			$localSettings .= "if( !array_key_exists( '$setting', \$GLOBALS ) ) {\n\t\$GLOBALS['$setting'] = array();\n}\n";
		}
		foreach( $configuration['arrays'] as $setting => $value ) {
			$localSettings .= self::writeArrayAssignment( $value, "\$$setting" );
		}

		# Skins loaded with wfLoadSkin
		if( $extensions['skin']['wfLoadSkin'] ) {
			$localSettings .= "\n# Skins\n";
			$localSettings .= $extensions['skin']['wfLoadSkin'];
		}

		# Extensions loaded with wfLoadExtension
		if( $extensions['extension']['wfLoadExtension'] ) {
			$localSettings .= "\n# Extensions\n";
			$localSettings .= $extensions['extension']['wfLoadExtension'];
		}

		# Self-register
		if( $configuration['extensions']['ExtensionMediaWikiFarm'][2] == 'require_once' ) {
			$localSettings .= "\n# Self-register\n";
			$localSettings .= "MediaWikiFarm::selfRegister();\n";
		}

		# Included files
		$localSettings .= "\n# Included files\n";
		foreach( $configuration['execFiles'] as $execFile ) {
			$localSettings .= "include '$execFile';\n";
		}

		if( $postconfig ) {
			$localSettings .= "\n" . $postconfig;
		}

		return $localSettings;
	}

	/**
	 * Write an 'array diff' (when only a subarray is modified) in plain PHP.
	 *
	 * Note that, given PHP lists and dictionaries use the same syntax, this function
	 * try to recognise a list when the array diff has exactly the keys 0, 1, 2, 3,…
	 * but there could be false positives.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @SuppressWarning(PHPMD.StaticAccess)
	 *
	 * @param array $array The 'array diff' (part of an array to be modified).
	 * @param string $prefix The beginning of the plain PHP, should be something like '$myArray'.
	 * @return string The plain PHP for this array assignment.
	 */
	public static function writeArrayAssignment( $array, $prefix ) {

		$result = '';
		$isList = ( count( array_diff( array_keys( $array ), range( 0, count( $array ) ) ) ) == 0 );
		foreach( $array as $key => $value ) {
			$newkey = '[' . var_export( $key, true ) . ']';
			if( $isList ) {
				$result .= $prefix . '[] = ' . var_export( $value, true ) . ";\n";
			} elseif( is_array( $value ) ) {
				$result .= self::writeArrayAssignment( $value, $prefix . $newkey );
			} else {
				$result .= $prefix . $newkey . ' = ' . var_export( $value, true ) . ";\n";
			}
		}

		return $result;
	}

	/**
	 * Composer key depending on the activated extensions and skins.
	 *
	 * Extension names should follow the form 'ExtensionMyWonderfulExtension';
	 * Skin names should follow the form 'SkinMyWonderfulSkin'.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param string $name Name of extension or skin.
	 * @return string Composer key.
	 */
	public static function composerKey( $name ) {

		if( $name == '' ) {
			return '';
		}

		return substr( md5( $name ), 0, 8 );
	}
}
