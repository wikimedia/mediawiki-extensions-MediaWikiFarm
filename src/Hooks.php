<?php
/**
 * Class MediaWikiFarmHooks.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

/**
 * MediaWiki hooks.
 */
class MediaWikiFarmHooks {

	/**
	 * Add files for unit testing.
	 *
	 * Only useful for MediaWiki 1.27- since MediaWiki 1.28+ autodiscovers these files.
	 * Given this hook is never useful at runtime, it should be moved to a separate file
	 * if MediaWiki runtime hooks are added in this file.
	 *
	 * @api
	 *
	 * @param string[] $files The test files.
	 * @return true
	 */
	public static function onUnitTestsList( array &$files ) {

		$dir = dirname( dirname( __FILE__ ) ) . '/tests/phpunit/';

		$files[] = $dir . 'ConfigurationTest.php';
		$files[] = $dir . 'ConstructionTest.php';
		$files[] = $dir . 'FunctionsTest.php';
		$files[] = $dir . 'HooksTest.php';
		$files[] = $dir . 'InstallationIndependantTest.php';
		$files[] = $dir . 'ListTest.php';
		$files[] = $dir . 'LoadingTest.php';
		$files[] = $dir . 'LoggingTest.php';
		$files[] = $dir . 'MonoversionInstallationTest.php';
		$files[] = $dir . 'MultiversionInstallationTest.php';
		$files[] = $dir . 'bin/MediaWikiFarmScriptComposerTest.php';
		$files[] = $dir . 'bin/MediaWikiFarmScriptTest.php';
		$files[] = $dir . 'bin/ScriptListWikisTest.php';

		return true;
	}
}
