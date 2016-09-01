<?php
/**
 * Abstract class MediaWikiFarmTestCase with basic stuff for tests.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */


abstract class MediaWikiFarmTestCase extends MediaWikiTestCase {

	/** @var string Configuration directory for tests. */
	static $wgMediaWikiFarmConfigDir = '';

	/** @var string Code directory for tests. */
	static $wgMediaWikiFarmCodeDir = '';

	/** @var string Cache directory for tests. */
	static $wgMediaWikiFarmCacheDir = '';

	/** @var array Array with boolean values if a given backuped global previously existed. */
	public $backupMWFGlobalsExist = array();

	/** @var array Array containing values of backuped globals. */
	public $backupMWFGlobals = array();

	/**
	 * Construct the test case.
	 *
	 * @param string $name Name of the test case.
	 * @param array $data
	 * @param string $dataname
	 * @return MediaWikiFarmTestCase
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {

		parent::__construct( $name, $data, $dataName );

		# MediaWikiTestCase disables the @backupGlobals in its constructor.
		# Although it speeds up greatly the tests, there is no more checks on global variables.
		# Uncomment it for that. When a lot of globals are changed, as it could be the case in
		# LoadingTest, PHPUnit takes a LOT of time to compute differences (10+ min), so the
		# backup* functions below restore the globals to the original value to make the diffs
		# easy to check and to declare what globals are to be changed in the test (other
		# changes will report the test as risky). MediaWiki equivalent functions (setMwGlobals)
		# were introduced in MW 1.21 and always assume the global exists, but in counterpart
		# they are more elaborated on serialisation heuristics.
		//$this->backupGlobals = true;

		$this->backupGlobalsBlacklist = array_merge(
			$this->backupGlobalsBlacklist,
			array(
				'wgExtensionFunctions',
				'wgHooks',
				'wgParamDefinitions',
			)
		);
	}

	/**
	 * Set up MediaWikiFarm parameters and versions files with the current MediaWiki installation.
	 */
	static function setUpBeforeClass() {

		global $IP;

		$dirIP = basename( $IP );

		# Set test configuration parameters
		self::$wgMediaWikiFarmConfigDir = dirname( __FILE__ ) . '/data/config';
		self::$wgMediaWikiFarmCodeDir = dirname( $IP );
		self::$wgMediaWikiFarmCacheDir = dirname( __FILE__ ) . '/data/cache';

		# Create versions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'atestfarm' => '$dirIP',
);

PHP;
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/versions.php', $versionsFile );

		# Create varwikiversions.php: the list of existing values for variable '$wiki' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'a' => '$dirIP',
);

PHP;
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/varwikiversions.php', $versionsFile );

		# Move http404.php to current directory - @todo: should be improved
		copy( self::$wgMediaWikiFarmConfigDir . '/http404.php', 'phpunitHTTP404.php' );
	}

	/**
	 * Remove cache directory and restore the globals which were declared as changeable.
	 */
	protected function tearDown() {

		wfRecursiveRemoveDir( self::$wgMediaWikiFarmCacheDir );

		# Restore backuped global variables
		$this->restoreSimpleGlobalVariables();

		parent::tearDown();
	}

	/**
	 * Remove config files.
	 */
	static function tearDownAfterClass() {

		unlink( self::$wgMediaWikiFarmConfigDir . '/versions.php' );
		unlink( self::$wgMediaWikiFarmConfigDir . '/varwikiversions.php' );
		unlink( 'phpunitHTTP404.php' );

		parent::tearDownAfterClass();
	}

	/**
	 * Backup a global variable and unset it.
	 *
	 * @param string $key Variable name.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	function backupAndUnsetGlobalVariable( $key ) {

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
	function backupAndSetGlobalVariable( $key, $value ) {

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
	function backupAndUnsetGlobalSubvariable( $key, $subkey ) {

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
	function backupAndSetGlobalSubvariable( $key, $subkey, $value ) {

		$this->backupGlobalSubvariable( $key, $subkey );
		$GLOBALS[$key][$subkey] = $value;
	}

	/**
	 * Backup a global variable.
	 *
	 * @param string $key Variable name.
	 * @return void
	 * @throws PHPUnit_Framework_RiskyTestError When the global variable cannot be backuped.
	 */
	function backupGlobalVariable( $key ) {

		if( !array_key_exists( $key, $GLOBALS ) ) {
			$this->backupMWFGlobalsExist[$key] = false;
		}
		elseif( !self::isRecursiveScalar( $GLOBALS[$key] ) && !$GLOBALS[$key] instanceof MediaWikiFarm ) {
			throw new PHPUnit_Framework_RiskyTestError( "Non-scalar backup of a global variable" );
		}
		else {
			$this->backupMWFGlobalsExist[$key] = true;
			$this->backupMWFGlobals[$key] = unserialize(serialize($GLOBALS[$key]));
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
	function backupGlobalSubvariable( $key, $subkey ) {

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
			$this->backupMWFGlobals[$key][$subkey] = unserialize(serialize($GLOBALS[$key][$subkey]));
		}
	}

	/**
	 * Restore backuped global variables.
	 *
	 * @return void
	 */
	function restoreSimpleGlobalVariables() {

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
	static function isRecursiveScalar( $value ) {

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
