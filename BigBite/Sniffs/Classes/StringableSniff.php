<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\ObjectDeclarations;

/**
 * Ensures classes that define a __toString method also implement the Stringable interface
 */
final class StringableSniff implements Sniff {

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
	 * @return array<int,int>
	 */
	public function register() {
		if ( version_compare( phpversion(), '8.0.0', '<' ) ) {
			return array();
		}

		return array( \T_FUNCTION );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token
	 *                                               in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();
		$fnName = $phpcsFile->findNext( T_STRING, $stackPtr, ( $stackPtr + 5 ), false, null, true );

		if ( false === $fnName || '__toString' !== $tokens[ $fnName ]['content'] ) {
			return;
		}

		$classDecl = $phpcsFile->findPrevious( T_CLASS, $stackPtr, 0 );

		// this __toString function isn't a class member?
		if ( false === $classDecl ) {
			return;
		}

		$interfaces = ObjectDeclarations::findImplementedInterfaceNames( $phpcsFile, $classDecl );

		// we're good - class containing __toString implements the interface.
		if (
			is_array( $interfaces ) && (
				in_array( '\\Stringable', $interfaces, true ) ||
				in_array( 'Stringable', $interfaces, true )
			)
		) {
			return;
		}

		$message = 'Classes that declare "__toString" should implement the Stringable interface.';
		$doWeFix = $phpcsFile->addFixableError( $message, $classDecl, 'NotImplemented', array(), 0 );

		if ( true !== $doWeFix ) {
			return;
		}

		$openingCurly = $tokens[ $classDecl ]['scope_opener'];

		if ( $tokens[ $openingCurly ]['line'] === $tokens[ $classDecl ]['line'] ) {
			$prevToken = $phpcsFile->findPrevious( T_STRING, $openingCurly, $classDecl );

			if ( false === $prevToken ) {
				return;
			}

			$phpcsFile->fixer->beginChangeset();
			$phpcsFile->fixer->addContent( $prevToken, ' implements \\Stringable' );
			$phpcsFile->fixer->endChangeset();

			return;
		}

		$endOfLine = $phpcsFile->findPrevious( T_WHITESPACE, $openingCurly, $classDecl );

		if ( false === $endOfLine ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContentBefore( $endOfLine, ' implements \\Stringable' );
		$phpcsFile->fixer->endChangeset();
	}
}
