<?php
/**
 * Class MediaWikiFarmHooks.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0, or (at your option) any later version.
 * @license AGPL-3.0+ GNU Affero General Public License v3.0, or (at your option) any later version.
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
		$files[] = $dir . 'LoadingTest.php';
		$files[] = $dir . 'LoggingTest.php';
		$files[] = $dir . 'MonoversionInstallationTest.php';
		$files[] = $dir . 'MultiversionInstallationTest.php';
		$files[] = $dir . 'bin/MediaWikiFarmScriptComposerTest.php';
		$files[] = $dir . 'bin/MediaWikiFarmScriptTest.php';

		return true;
	}
}
