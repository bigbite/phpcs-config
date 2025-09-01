<?php
/**
 * Unit test class for BigBite Coding Standard.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Tests\Files;

use BigBiteCS\BigBite\Tests\AbstractSniffUnitTest;

/**
 * Unit test class for the DeclareStatement sniff.
 *
 * @package BigBiteCS\BigBite
 */
final class DeclareStatementUnitTest extends AbstractSniffUnitTest {

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
			case 'DeclareStatementUnitTest.1.inc':
				return array(
					2  => 4,
					3  => 5,
					4  => 4,
					5  => 6,
					6  => 5,
					7  => 4,
					8  => 4,
					9  => 6,
					10 => 4,
					11 => 5,
					12 => 3,
					15 => 1,
					16 => 3,
					19 => 3,
					20 => 2,
					23 => 6,
					25 => 5,
					29 => 5,
					32 => 1,
					34 => 5,
					36 => 5,
					38 => 4,
					42 => 1,
					43 => 3,
					45 => 4,
					48 => 4,
					51 => 5,
					54 => 5,
					55 => 1,
					57 => 5,
					60 => 0,
					62 => 1,
					63 => 1,
				);
			case 'DeclareStatementUnitTest.2.inc':
				return array();
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
	 * @return array<int,int>
	 */
	public function getWarningList() {
		return array();
	}
}
