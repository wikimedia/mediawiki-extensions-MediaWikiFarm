<?php
/**
 * Class MediaWikiFarmScriptComposer.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

// @codeCoverageIgnoreStart
require_once dirname( __FILE__ ) . '/AbstractMediaWikiFarmScript.php';
// @codeCoverageIgnoreEnd

/**
 * Wrapper around Composer to create as many autoloaders as MediaWiki extensions.
 *
 * This class contains the major part of the script utility, mainly in the main() method.
 * Using a class instead of a raw script it better for testability purposes and to use
 * less global variables (in fact none; the only global variable written are for
 * compatibility purposes, e.g. PHPUnit expects $_SERVER['argv']).
 */
class MediaWikiFarmScriptComposer extends AbstractMediaWikiFarmScript {

	/**
	 * Create the object with a copy of $argc and $argv.
	 *
	 * @api
	 *
	 * @param int $argc Number of input arguments.
	 * @param string[] $argv Input arguments.
	 * @return MediaWikiFarmScript
	 */
	public function __construct( $argc, $argv ) {

		parent::__construct( $argc, $argv );

		$this->shortUsage = "
    Usage: php {$this->argv[0]} …

    You must be inside a Composer-managed MediaWiki directory.

    Parameters: regular Composer parameters
";

		$fullPath = realpath( $this->argv[0] );
		$this->longUsage = "    | For easier use, you can alias it in your shell:
    |
    |     alias mwcomposer='php $fullPath'
    |
    | Return codes:
    | 0 = success
    | 4 = user error, like a missing parameter (similar to HTTP 400)
    | 5 = internal error in farm configuration (similar to HTTP 500)
";
	}

	/**
	 * Main program for the script.
	 *
	 * Although it returns void, the 'status' property says if there was an error or not.
	 *
	 * @api
	 *
	 * @return void
	 */
	public function main() {

		# Manage mandatory arguments.
		if( !$this->premain() ) {
			return false;
		}

		# Get current directory
		$cwd = getcwd();
		$this->getParam( 0 );

		# Get 'quiet' argument
		$quiet = false;
		foreach( $this->argv as $arg ) {
			if( $arg == '-q' ) {
				$quiet = true;
			}
		}

		if( !MediaWikiFarmUtils::isMediaWiki( $cwd ) || !is_file( 'composer.json' ) ) {
			$this->usage();
			$this->status = 4;
			return false;
		}

		# Backup composer.json and copy MediaWiki directory in temporary dir to
		# change its vendor directory and extensions without breaking current
		# installation
		$origComposerJson = file_get_contents( 'composer.json' );
		$tmpDir = tempnam( sys_get_temp_dir(), 'mwcomposer' );
		unlink( $tmpDir );
		mkdir( $tmpDir );
		self::copyr( $cwd, $tmpDir, true, array( '/extensions', '/skins', '/vendor', '/composer\.lock' ) );
		chdir( $tmpDir );

		# Update complete dependencies from Composer
		if( !$quiet ) {
			echo "\n0. Composer with complete extensions/skins set:\n"; // @codeCoverageIgnore
		}
		// @codingStandardsIgnoreLine MediaWiki.Usage.ForbiddenFunctions.system
		system( 'composer ' . implode( ' ', $this->argv ), $return );
		if( $return ) {
			// @codeCoverageIgnoreStart
			chdir( $cwd );
			self::rmdirr( $tmpDir );
			$this->status = 5;
			return false;
			// @codeCoverageIgnoreEnd
		}

		# Copy complete dependencies into 'read-only' directories
		self::copyr( 'extensions', 'extensions-composer', true );
		self::copyr( 'skins', 'skins-composer', true );
		self::copyr( 'vendor', 'vendor-composer', true );
		if( is_file( 'composer.local.json' ) ) {
			unlink( 'composer.local.json' );
		}

		# Get installed extensions
		$installedJson = file_get_contents( 'vendor/composer/installed.json' );
		if( !$installedJson ) {
			$this->status = 5; // @codeCoverageIgnore
			return false; // @codeCoverageIgnore
		}
		$installedJson = json_decode( $installedJson, true );
		$baseComposerJson = json_decode( $origComposerJson, true );

		# Composer v2 - https://github.com/composer/composer/commit/60df8925174dfb385368efbbfd2d19c7f372c2cd
		if( array_key_exists( 'packages', $installedJson ) ) {
			$installedJson = $installedJson['packages'];
		}

		$installable = array();
		$extensions = array();
		$dependencies = array();
		foreach( $installedJson as $package ) {
			if( $package['type'] == 'mediawiki-extension' || $package['type'] == 'mediawiki-skin' ) {
				$installable[$package['name']] = $package['version_normalized'];
				$extensions[$package['name']] = self::composer2mediawiki( $package['name'], $package['type'] );
				if( array_key_exists( 'require', $baseComposerJson ) && array_key_exists( $package['name'], $baseComposerJson['require'] ) ) {
					unset( $baseComposerJson['require'][$package['name']] );
				}
				if( array_key_exists( 'require-dev', $baseComposerJson ) && array_key_exists( $package['name'], $baseComposerJson['require-dev'] ) ) {
					unset( $baseComposerJson['require-dev'][$package['name']] );
				}
				$dependencies[$extensions[$package['name']]] = array_key_exists( 'require', $package ) ? array_keys( $package['require'] ) : array();
			}
		}

		# Remove if empty, else it would create an empty JSON list but the schema expects it is an object (PHP has no
		# such distinction); the option JSON_FORCE_OBJECT is not appropriate because it always transforms lists to objects
		if( array_key_exists( 'require-dev', $baseComposerJson ) && count( $baseComposerJson['require-dev'] ) == 0 ) {
			unset( $baseComposerJson['require-dev'] );
		}
		asort( $extensions );
		ksort( $dependencies );
		if( !$quiet ) {
			// @codeCoverageIgnoreStart
			echo "Complete extensions/skins set composed of:\n";
			foreach( $installable as $name => $version ) {
				echo '* ' . preg_replace( '/^(Extension|Skin)/', '$1 ', $extensions[$name] ) . " ($version)\n";
			}
			// @codeCoverageIgnoreEnd
		}
		self::copyr( 'vendor/composer', 'vendor-composer/composer-init', true );
		self::rmdirr( 'composer.lock' );

		# Filter MediaWiki extensions/skins dependencies
		foreach( $dependencies as $name => &$deps ) {

			$newdeps = array();
			foreach( $deps as $dep ) {
				if( array_key_exists( $dep, $extensions ) ) {
					$newdeps[] = $extensions[$dep];
				}
			}
			$deps = $newdeps;
		}

		# Iterate over installable extensions/skins
		$icounter = 1;
		foreach( $extensions as $composerName => $name ) {

			$thisInstallation = $baseComposerJson;
			$thisInstallation['require'][$composerName] = $installable[$composerName];
			$thisInstallation['config']['autoloader-suffix'] = MediaWikiFarmConfiguration::composerKey( $name );

			if( !$quiet ) {
				// @codeCoverageIgnoreStart
				echo "\n$icounter. Composer set for ";
				echo lcfirst( preg_replace( '/^(Extension|Skin)/', '$1 ', $extensions[$composerName] ) ) . ' (';
				echo MediaWikiFarmConfiguration::composerKey( $name ) . "):\n";
				// @codeCoverageIgnoreEnd
			}

			self::rmdirr( 'vendor/autoload.php' );
			file_put_contents( 'composer.json', json_encode( $thisInstallation ) );
			// @codingStandardsIgnoreLine MediaWiki.Usage.ForbiddenFunctions.system
			system( 'composer ' . implode( ' ', $this->argv ), $return );
			if( $return ) {
				// @codeCoverageIgnoreStart
				chdir( $cwd );
				self::rmdirr( $tmpDir );
				$this->status = 5;
				return false;
				// @codeCoverageIgnoreEnd
			}

			self::copyr( 'vendor/composer', 'vendor-composer/composer' . MediaWikiFarmConfiguration::composerKey( $name ),
			             true, array(), array( '/autoload_.*\.php', '/ClassLoader\.php', '/installed\.json', '/include_paths\.php' )
			);
			self::rmdirr( 'composer.lock' );
			$icounter++;
		}

		# Finally the Composer set without any extension/skin
		$thisInstallation = $baseComposerJson;
		$thisInstallation['config']['autoloader-suffix'] = 'DEFAULT';
		if( array_key_exists( 'require', $baseComposerJson ) && count( $baseComposerJson['require'] ) == 0 ) {
			unset( $thisInstallation['require'] );
		}

		if( !$quiet ) {
			echo "\n$icounter. Composer with empty extensions/skins set:\n"; // @codeCoverageIgnore
		}

		self::rmdirr( 'vendor/autoload.php' );
		file_put_contents( 'composer.json', json_encode( $thisInstallation ) );
		// @codingStandardsIgnoreLine MediaWiki.Usage.ForbiddenFunctions.system
		system( 'composer ' . implode( ' ', $this->argv ), $return );
		if( $return ) {
			// @codeCoverageIgnoreStart
			chdir( $cwd );
			self::rmdirr( $tmpDir );
			$this->status = 5;
			return false;
			// @codeCoverageIgnoreEnd
		}

		# Merge things other than autoloader_*.php, e.g. subdirectory 'installers'
		self::copyr( 'vendor/composer', 'vendor-composer/composer-init', false );
		self::copyr( 'vendor-composer/composer-init', 'vendor-composer/composer', true );
		self::rmdirr( 'vendor-composer/composer-init', true );

		# Put autoloader indirection
		copy( dirname( dirname( __FILE__ ) ) . '/MediaWikiFarmComposerAutoloader.php', 'vendor-composer/autoload.php' );

		# Copy the directories back to the original MediaWiki: vendor, extensions, and skins
		self::copyr( 'vendor-composer', $cwd . '/vendor', true );
		self::copyr( 'composer.lock', $cwd );
		if( is_dir( 'extensions-composer' ) ) {
			$files = array_diff( scandir( 'extensions-composer' ), array( '.', '..' ) );
			foreach( $files as $file ) {
				self::copyr( 'extensions-composer/' . $file, $cwd . '/extensions/' . $file, true );
			}
		}
		if( is_dir( 'skins-composer' ) ) {
			$files = array_diff( scandir( 'skins-composer' ), array( '.', '..' ) );
			foreach( $files as $file ) {
				self::copyr( 'skins-composer/' . $file, $cwd . '/skins/' . $file, true );
			}
		}
		$phpDependencies = "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn " . var_export( $dependencies, true ) . ';';
		file_put_contents( $cwd . '/vendor/MediaWikiExtensions.php', $phpDependencies );

		chdir( $cwd );
		self::rmdirr( $tmpDir );
	}

	/**
	 * Mapping between a Composer key and a MediaWikiFarm key.
	 *
	 * The MediaWikiFarm key is the canonical MediaWiki name prefixed by "Extension" or "Skin".
	 *
	 * @internal
	 *
	 * @param string $name Name of a Composer package.
	 * @param string $type Composer type.
	 * @return string MediaWikiFarm key.
	 */
	public static function composer2mediawiki( $name, $type ) {

		$name = explode( '/', $name );
		$name = $name[1];
		if( $type == 'mediawiki-extension' ) {
			$name = preg_replace( '/-extension$/', '', $name );
			$name = str_replace( '-', ' ', $name );
			$name = str_replace( ' ', '', ucwords( $name ) );
			$name = 'Extension' . $name;
		} elseif( $type == 'mediawiki-skin' )  {
			$name = preg_replace( '/-skin$/', '', $name );
			$name = 'Skin' . $name;
		}

		return $name;
	}
}
