<?php
/**
 * Class MediaWikiFarmList.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

/**
 * Census of the wikis managed by the farms.
 */
class MediaWikiFarmList {

	/** @var string Farm configuration directory. */
	protected $configDir = '';

	/** @var string|false MediaWiki cache directory. */
	protected $cacheDir = '/tmp/mw-cache';

	/** @var array Logs. */
	public $log = array();

	/** @var array Farms. */
	public $farms;


	/**
	 * Construction.
	 *
	 * @param string $configDir Configuration directory.
	 * @param string|false $cacheDir Cache directory; if false, the cache is disabled.
	 * @return MediaWikiFarmList
	 */
	public function __construct( $configDir, $cacheDir ) {

		if( !is_string( $configDir ) || !is_dir( $configDir ) ) {
			throw new InvalidArgumentException( 'Invalid directory for the farm configuration.' );
		}
		if( !is_string( $cacheDir ) && $cacheDir !== false ) {
			throw new InvalidArgumentException( 'Cache directory must be false or a directory.' );
		}

		$this->configDir = $configDir;
		$this->cacheDir = $cacheDir;
		$log = array();

		list( $this->farms, /* unused */ ) = MediaWikiFarmUtils::readAnyFile( 'farms', $this->configDir, $this->cacheDir, $log );
	}

	/**
	 * Get list of wikis URLs from a specific farm or all farms.
	 *
	 * For a list of variables [ 'v1' => [ 'a', 'b' ], 'v2' => [ 'c', 'd' ] ] and a
	 * server regex '$v1\.$v2\.example\.org', the result is:
	 * [ 'a.c.example.org', 'a.d.example.org', 'b.c.example.org', 'b.d.example.org' ]
	 *
	 * @param string|null $farmName Farm name to return the URLs from, null for all farms.
	 * @return string[] List of wikis URLs.
	 */
	public function getURLsList( $farmName = null ) {

		$urlsList = array();

		if( $farmName === null ) {

			foreach( $this->farms as $farmName => $v ) {
				$urlsList = array_merge( $urlsList, $this->getURLsList( $farmName ) );
			}
		}
		elseif( is_string( $farmName ) ) {

			if( array_key_exists( 'redirect', $this->farms[$farmName] ) ) {
				return $urlsList;
			}

			$server = $this->farms[$farmName]['server'];
			$variables = $this->farms[$farmName]['variables'];
			$variablesList = $this->getVariablesList( $farmName );
			foreach( $variablesList as $vlist ) {
				$url = $server;
				foreach( $variables as $variable ) {
					$url = preg_replace( "/(\(\?P?<{$variable['variable']}>)(.*?)(\))/", $vlist[$variable['variable']], $url );
				}
				$url = str_replace( '\.', '.', $url );
				$urlsList[] = $url;
			}
		}

		return $urlsList;
	}

	/**
	 * Get list of wikis from a specific farm.
	 *
	 * @param string $farmName Farm name to get the variables from.
	 * @return string[] List of wikis from the farm.
	 */
	public function getVariablesList( $farmName ) {

		$farm = $this->farms[$farmName];

		$tree = $this->obtainVariables( $farm['variables'] );
		return $this->generateVariablesList( $tree, $farm['variables'] );
	}

	/**
	 * Obtain the values of each declared variable.
	 *
	 * For a list of variables [ 'v1' => [ 'a', 'b' ], 'v2' => [ 'c', 'd' ] ], the result is:
	 * [ 'a' => [ 'c' => 0, 'd' => 1 ], 'b' => [ 'c' => 0, 'd' => 1 ] ].
	 *
	 * @param array[] $variables Parameter 'variables' from farms.yml.
	 * @return array[] List of values for each variable.
	 */
	public function obtainVariables( $variables ) {

		$tree = array();

		$variable = array_shift( $variables );

		if( array_key_exists( 'file', $variable ) ) {
			$list = MediaWikiFarmUtils::readFile( $variable['file'], $this->cacheDir, $this->log, $this->configDir );
			$values = array();
			foreach( $list as $key => $value ) {
				if( is_string( $key ) ) {
					$values[] = $key;
				} elseif( is_string( $value ) ) {
					$values[] = $value;
				}
			}
			$tree = array_flip( $values );
		} else {
			$tree = array( 0 => null );
		}

		if( count( $variables ) ) {
			foreach( $tree as $k => &$v ) {
				$variables2 = $variables;
				foreach( $variables2 as &$v2 ) {
					if( array_key_exists( 'file', $v2 ) ) {
						$v2['file'] = str_replace( '$' . $variable['variable'], $k, $v2['file'] );
					}
				}
				$v = $this->obtainVariables( $variables2 );
			}
		}

		return $tree;
	}

	/**
	 * Generate (explicit) combinaisons for the different variables.
	 *
	 * For a list of variables [ 'v1' => [ 'a', 'b' ], 'v2' => [ 'c', 'd' ] ], the result is:
	 * [ [ 'v1' => 'a', 'v2' => 'c' ],
	 *   [ 'v1' => 'a', 'v2' => 'd' ],
	 *   [ 'v1' => 'b', 'v2' => 'c' ],
	 *   [ 'v1' => 'b', 'v2' => 'd' ] ]
	 *
	 * @param array[] $tree Lists of values for each variable.
	 * @param array[] $variables Parameter 'variables' from farms.yml.
	 * @return array[] Dictionaries with all possible (explicit) combinaisons of the variables.
	 */
	public function generateVariablesList( $tree, $variables ) {

		$list = array();

		$name = $variables[0]['variable'];
		array_shift( $variables );

		foreach( $tree as $k => $v ) {
			if( $v === null ) {
				return array();
			}
			/*elseif( $k === 0 ) {
				$list = $this->generateVariablesList( $v );
				break;
			}*/
			else {
				if( count( $variables ) ) {
					$sublist = $this->generateVariablesList( $v, $variables );
					foreach( $sublist as $k2 ) {
						$list[] = array_merge( array( $name => $k ), $k2 );
					}
				}
				else {
					$list[] = array( $name => $k );
				}
			}
		}

		return $list;
	}
}
