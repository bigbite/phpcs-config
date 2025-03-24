<?php
/**
 * Unit test class for BigBite Coding Standard.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Tests\Objects;

use BigBiteCS\BigBite\Tests\AbstractSniffUnitTest;

/**
 * Unit test class for the NewLineAfterOpeningBrace sniff.
 *
 * @package BigBiteCS\BigBite
 */
final class NewLineAfterOpeningBraceUnitTest extends AbstractSniffUnitTest {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array<int,string>
	 */
	public $supportedTokenizers = array(
		'PHP',
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int,int|string>
	 */
	public function register() {
		return array(
			\T_CLASS,
			\T_ENUM,
			\T_INTERFACE,
			\T_TRAIT,
		);
	}

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
			case 'NewLineAfterOpeningBraceUnitTest.1.inc':
				return array(
					3  => 1,
					7  => 1,
					13 => 1,
					17 => 1,
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
