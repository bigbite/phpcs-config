<?php
/**
 * Unit test class for BigBite Coding Standard.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Tests\Commenting;

use BigBiteCS\BigBite\Tests\AbstractSniffUnitTest;

/**
 * Unit test class for the DocCommentLineLength sniff.
 *
 * @package BigBiteCS\BigBite
 */
final class DocCommentLineLengthUnitTest extends AbstractSniffUnitTest {

	/**
	 * Get a list of CLI values to set before the file is tested.
	 *
	 * @param string                  $testFile The name of the file being tested.
	 * @param \PHP_CodeSniffer\Config $config   The config data for the test run.
	 *
	 * @return void
	 */
	public function setCliValues( $testFile, $config ) {
		$config->tabWidth = 4;
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
			case 'DocCommentLineLengthUnitTest.1.inc':
				return array(
					8  => 1,
					39 => 1,
				);
			case 'DocCommentLineLengthUnitTest.2.inc':
				return array(
					15 => 1,
				);
			case 'DocCommentLineLengthUnitTest.3.inc':
				return array(
					11 => 1,
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
		switch ( $testFile ) {
			case 'DocCommentLineLengthUnitTest.1.inc':
				return array(
					4  => 1,
					32 => 1,
				);
			case 'DocCommentLineLengthUnitTest.2.inc':
				return array(
					11 => 1,
				);
			case 'DocCommentLineLengthUnitTest.3.inc':
				return array(
					7 => 1,
				);
			default:
				return array();
		}
	}
}
