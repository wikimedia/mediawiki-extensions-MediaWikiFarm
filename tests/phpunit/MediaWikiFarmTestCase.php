<?php
/**
 * Class MediaWikiFarmTestCase.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 *
 * @codingStandardsIgnoreFile MediaWiki.Files.OneClassPerFile.MultipleFound
 */

require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/bin/AbstractMediaWikiFarmScript.php';

# These tests can be called either directly with PHPUnit or through the PHPUnit infrastructure
# inside MediaWiki (the wrapper tests/phpunit/phpunit.php).
# When executing PHPUnit alone, this class does not exist
if( !class_exists( 'MediaWikiTestCase' ) ) {

	# PHPUnit ≥ 6.0
	if( class_exists( 'PHPUnit\Framework\TestCase' ) ) {

		/**
		 * Placeholder for MediaWikiTestCase when standalone PHPUnit is executed.
		 *
		 * Use a class alias instead of creating a new MediaWikiTestCase.
		 * Wrap the alias in a call_user_func call to avoid making IDEs think this is the main
		 * MediaWikiTestCase definition.
		 */
		call_user_func( 'class_alias', 'PHPUnit\Framework\TestCase', 'MediaWikiTestCase' );
	}

	# PHPUnit < 6.0
	elseif( class_exists( 'PHPUnit_Framework_TestCase' ) ) {

		/**
		 * Placeholder for MediaWikiTestCase when standalone PHPUnit is executed.
		 *
		 * Use a class alias instead of creating a new MediaWikiTestCase.
		 * Wrap the alias in a call_user_func call to avoid making IDEs think this is the main
		 * MediaWikiTestCase definition.
		 */
		call_user_func( 'class_alias', 'PHPUnit_Framework_TestCase', 'MediaWikiTestCase' );
	}
}

/**
 * Abstract class with basic stuff for tests.
 *
 * @group MediaWikiFarm
 */
abstract class MediaWikiFarmTestCase extends MediaWikiTestCase {

	/** @var string Farm code directory. */
	public static $wgMediaWikiFarmFarmDir = '';

	/** @var string Data directory for tests. */
	public static $wgMediaWikiFarmTestDataDir = '';

	/** @var string Configuration directory for tests. */
	public static $wgMediaWikiFarmConfigDir = '';

	/** @var string Configuration directory (2) for tests. */
	public static $wgMediaWikiFarmConfig2Dir = '';

	/** @var string Code directory created for tests. */
	public static $wgMediaWikiFarmCodeDir = '';

	/** @var string Cache directory for tests. */
	public static $wgMediaWikiFarmCacheDir = '';

	/** @var string Syslog tag for tests. */
	public static $wgMediaWikiFarmSyslog = '';

	/** @var array Array with boolean values if a given backuped global previously existed. */
	public $backupMWFGlobalsExist = array();

	/** @var array Array containing values of backuped globals. */
	public $backupMWFGlobals = array();

	/**
	 * Construct the test case.
	 *
	 * @param string|null $name Name of the test case.
	 * @param array $data Data for data providers.
	 * @param string $dataName Name of the data for data providers.
	 * @return MediaWikiFarmTestCase
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {

		parent::__construct( $name, $data, $dataName );

		# MediaWikiTestCase disables the @backupGlobals in its constructor.
		# Although it speeds up greatly the tests, there is no more checks on global variables.
		# This restores the defaut value (case-by-case choice). When a lot of globals are changed,
		# as it could be the case in LoadingTest, PHPUnit takes a LOT of time to compute differences
		# (10+ min), so the backup* functions below restore the globals to the original value to
		# make the diffs easy to check and to declare what globals are to be changed in the test
		# (other changes will report the test as risky). MediaWiki equivalent functions (setMwGlobals)
		# were introduced in MW 1.21 and always assume the global exists, but in counterpart
		# they are more elaborated on serialisation heuristics.
		$this->backupGlobals = null;

		# Closures are thought to be serialisable although they are not, so blacklist them
		# sebastian/global-state was improved on this point since version 3.0
		$this->backupGlobalsBlacklist = array_merge(
			$this->backupGlobalsBlacklist,
			array(
				'factory',
				'parserMemc',
				'wgExtensionFunctions',
				'wgHooks',
				'wgParamDefinitions',
				'wgParser',
				'wgFlowActions',
				'wgJobClasses',
				'wgReadOnly', // T163640 - bug in PHPUnit subprogram global-state (issue #10)
			)
		);
	}

	/**
	 * Set up MediaWikiFarm parameters and versions files with the current MediaWiki installation.
	 */
	public static function setUpBeforeClass() {

		# Set test configuration parameters
		self::$wgMediaWikiFarmFarmDir = dirname( dirname( dirname( __FILE__ ) ) );
		self::$wgMediaWikiFarmTestDataDir = dirname( __FILE__ ) . '/data';
		self::$wgMediaWikiFarmConfigDir = dirname( __FILE__ ) . '/data/config';
		self::$wgMediaWikiFarmConfig2Dir = dirname( __FILE__ ) . '/data/config2';
		self::$wgMediaWikiFarmCodeDir = dirname( __FILE__ ) . '/data/mediawiki';
		self::$wgMediaWikiFarmCacheDir = dirname( __FILE__ ) . '/data/cache';
		self::$wgMediaWikiFarmSyslog = 'mediawikifarm';

		# Move http404.php to current directory - @todo: should be improved
		copy( self::$wgMediaWikiFarmConfigDir . '/http404.php', 'phpunitHTTP404.php' );

		# Dynamically create these files to avoid CI error reports
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/badsyntax.php', "<?php\nreturn array()\n" );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/badsyntax.json', "{\n\t\"element1\",\n}\n" );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/empty.json', "null\n" );
		file_put_contents( self::$wgMediaWikiFarmTestDataDir . '/readAnyFile/badsyntax.json', "{\n\t\"element1\",\n}\n" );
	}

	/**
	 * Remove cache directory and restore the globals which were declared as changeable.
	 */
	protected function tearDown() {

		if( is_dir( self::$wgMediaWikiFarmCacheDir ) ) {
			AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCacheDir );
		}

		# Restore backuped global variables
		$this->restoreSimpleGlobalVariables();

		parent::tearDown();
	}

	/**
	 * Remove config files.
	 */
	public static function tearDownAfterClass() {

		if( is_file( 'phpunitHTTP404.php' ) ) {
			unlink( 'phpunitHTTP404.php' );
		}
		if( is_file( self::$wgMediaWikiFarmConfigDir . '/badsyntax.php' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/badsyntax.php' );
		}
		if( is_file( self::$wgMediaWikiFarmConfigDir . '/badsyntax.json' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/badsyntax.json' );
		}
		if( is_file( self::$wgMediaWikiFarmConfigDir . '/empty.json' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/empty.json' );
		}
		if( is_file( self::$wgMediaWikiFarmTestDataDir . '/readAnyFile/badsyntax.json' ) ) {
			unlink( self::$wgMediaWikiFarmTestDataDir . '/readAnyFile/badsyntax.json' );
		}

		parent::tearDownAfterClass();
	}

	/**
	 * Backup a global variable and unset it.
	 *
	 * @param string $key Variable name.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	public function backupAndUnsetGlobalVariable( $key ) {

		$this->backupGlobalVariable( $key );
		unset( $GLOBALS[$key] );
	}

	/**
	 * Backup a global variable and set it.
	 *
	 * @param string $key Variable name.
	 * @param mixed $value New variable value.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	public function backupAndSetGlobalVariable( $key, $value ) {

		$this->backupGlobalVariable( $key );
		$GLOBALS[$key] = $value;
	}

	/**
	 * Backup a global array variable and unset it.
	 *
	 * @param string $key Variable name.
	 * @param string $subkey Subkey variable name.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	public function backupAndUnsetGlobalSubvariable( $key, $subkey ) {

		$this->backupGlobalSubvariable( $key, $subkey );
		unset( $GLOBALS[$key][$subkey] );
	}

	/**
	 * Backup a global array variable and set it.
	 *
	 * @param string $key Variable name.
	 * @param string $subkey Subkey variable name.
	 * @param mixed $value New variable value.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	public function backupAndSetGlobalSubvariable( $key, $subkey, $value ) {

		$this->backupGlobalSubvariable( $key, $subkey );
		$GLOBALS[$key][$subkey] = $value;
	}

	/**
	 * Backup global variables.
	 *
	 * @param string[] $keys Variable names.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When a global variable cannot be backuped.
	 */
	public function backupGlobalVariables( $keys ) {

		foreach( $keys as $key ) {
			$this->backupGlobalVariable( $key );
		}
	}

	/**
	 * Backup a global variable.
	 *
	 * @param string $key Variable name.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	public function backupGlobalVariable( $key ) {

		if( !array_key_exists( $key, $GLOBALS ) ) {
			$this->backupMWFGlobalsExist[$key] = false;
		}
		elseif( !self::isRecursiveScalar( $GLOBALS[$key] ) && !$GLOBALS[$key] instanceof MediaWikiFarm ) {
			throw new PHPUnit_Framework_RiskyTestError( "Non-scalar backup of a global variable" );
		}
		else {
			$this->backupMWFGlobalsExist[$key] = true;
			$this->backupMWFGlobals[$key] = unserialize( serialize( $GLOBALS[$key] ) );
		}
	}

	/**
	 * Backup a global array variable.
	 *
	 * @param string $key Variable name.
	 * @param string $subkey Subkey variable name.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	public function backupGlobalSubvariable( $key, $subkey ) {

		if( !array_key_exists( $key, $GLOBALS ) ) {
			throw new PHPUnit_Framework_RiskyTestError( 'Requested array backup of a global subvariable but nonexistent global variable' );
		}
		if( !is_array( $GLOBALS[$key] ) ) {
			throw new PHPUnit_Framework_RiskyTestError( 'Requested array backup of a global variable but non-array global variable' );
		}
		if( !array_key_exists( $key, $this->backupMWFGlobalsExist ) ) {
			$this->backupMWFGlobalsExist[$key] = array();
			$this->backupMWFGlobals[$key] = array();
		}

		if( !array_key_exists( $subkey, $GLOBALS[$key] ) ) {
			$this->backupMWFGlobalsExist[$key][$subkey] = false;
		}
		elseif( !self::isRecursiveScalar( $GLOBALS[$key][$subkey] ) ) {
			throw new PHPUnit_Framework_RiskyTestError( 'Non-scalar backup of a global variable (array)' );
		}
		else {
			$this->backupMWFGlobalsExist[$key][$subkey] = true;
			$this->backupMWFGlobals[$key][$subkey] = unserialize( serialize( $GLOBALS[$key][$subkey] ) );
		}
	}

	/**
	 * Restore backuped global variables.
	 *
	 * @return void
	 */
	public function restoreSimpleGlobalVariables() {

		foreach( $this->backupMWFGlobalsExist as $key => $existence ) {

			if( $existence === false ) {
				unset( $GLOBALS[$key] );
			}
			elseif( $existence === true ) {
				$GLOBALS[$key] = $this->backupMWFGlobals[$key];
			}
			elseif( is_array( $existence ) ) {
				foreach( $existence as $subkey => $subexistence ) {
					if( $subexistence === false ) {
						unset( $GLOBALS[$key][$subkey] );
					}
					elseif( $subexistence === true ) {
						$GLOBALS[$key][$subkey] = $this->backupMWFGlobals[$key][$subkey];
					}
				}
			}
		}
	}

	/**
	 * Say if a given value is a scalar or an array of scalars.
	 *
	 * @param mixed $value Value to be checked.
	 * @return bool The value is a scalar or an array of scalars.
	 */
	public static function isRecursiveScalar( $value ) {

		if( is_scalar( $value ) || $value === null ) {
			return true;
		}
		elseif( is_array( $value ) ) {
			foreach( $value as $key => $subvalue ) {
				if( !self::isRecursiveScalar( $subvalue ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
}
