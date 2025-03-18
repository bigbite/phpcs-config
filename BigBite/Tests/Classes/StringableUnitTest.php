<?php
/**
 * Unit test class for BigBite Coding Standard.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Tests\Classes;

use BigBiteCS\BigBite\Tests\AbstractSniffUnitTest;

/**
 * Unit test class for the Stringable sniff.
 *
 * @package BigBiteCS\BigBite
 */
final class StringableUnitTest extends AbstractSniffUnitTest {

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
		if ( version_compare( phpversion(), '8.0.0', '<' ) ) {
			return array();
		}

		switch ( $testFile ) {
			case 'StringableUnitTest.1.inc':
				return array(
					12 => 1,
					18 => 1,
					24 => 1,
				);
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
	 * @param string $testFile The name of the file being tested.
	 *
	 * @return array<int,int>
	 */
	public function getWarningList( $testFile = '' ) {
		return array();
	}
}
