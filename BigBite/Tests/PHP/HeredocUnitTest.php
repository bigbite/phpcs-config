<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Tests\PHP;

use BigBiteCS\BigBite\Tests\AbstractSniffUnitTest;

/**
 * Unit test class for the Heredoc sniff.
 *
 * @package BigBiteCS\BigBite
 */
final class HeredocUnitTest extends AbstractSniffUnitTest {

	/**
	 * Returns the lines where errors should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of errors that should occur on that line.
	 *
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int,int>
	 */
	public function getErrorList( $testFile = '' ) {
		switch ( $testFile ) {
			case 'HeredocUnitTest.1.inc':
				return array( 2 => 1 );
			default:
				return array();
		}
	}

	/**
	 * Returns the lines where warnings should occur.
	 *
	 * The key of the array should represent the line number and the value
	 * should represent the number of warnings that should occur on that line.
	 *
	 * @return array<int, int>
	 */
	public function getWarningList() {
		return array();
	}
}
