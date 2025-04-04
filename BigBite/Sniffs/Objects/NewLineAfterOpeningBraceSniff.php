<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Sniffs\Objects;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures a blank line between the object definition and its first content.
 */
final class NewLineAfterOpeningBraceSniff implements Sniff {

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

		$openingLineNo = $tokens[ $stackPtr ]['line'];
		$openingBrace  = $tokens[ $stackPtr ]['scope_opener'];
		$closingBrace  = $tokens[ $stackPtr ]['scope_closer'];
		$closingLineNo = $tokens[ $closingBrace ]['line'];

		// Other sniffs will handle objects with empty or malformed bodies.
		if ( $openingLineNo === $closingLineNo || ( $openingLineNo + 1 ) === $closingLineNo ) {
			return;
		}

		$nextContent = $phpcsFile->findNext( \T_WHITESPACE, ( $openingBrace + 1 ), null, true );

		// Blank line exists between opening brace and content.
		if ( ( $tokens[ $openingBrace ]['line'] + 2 ) === $tokens[ $nextContent ]['line'] ) {
			return;
		}

		$error = 'There must be exactly one blank line between the %s definition and its first content.';
		$data  = array( $tokens[ $stackPtr ]['content'] );
		$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NotFound', $data );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addNewline( $openingBrace );
		$phpcsFile->fixer->endChangeset();
	}
}
