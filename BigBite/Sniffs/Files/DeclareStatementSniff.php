<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the format of the declare statements.
 */
final class DeclareStatementSniff implements Sniff {

	/**
	 * The file's line endings.
	 *
	 * @var string
	 */
	public $lineEnding = PHP_EOL;

	/**
	 * Valid directives for a declare statement
	 *
	 * @var array<int,string>
	 */
	public $validDirectives = array( 'encoding', 'strict_types', 'ticks' );

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int|int>
	 */
	public function register() {
		return array( \T_DECLARE );
	}

	/**
	 * Processes this sniff, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $stackPtr  The position of the current token in
	 *                                               the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$this->lineEnding = $phpcsFile->eolChar;

		$tokens = $phpcsFile->getTokens();

		$endOfStatement = $phpcsFile->findEndOfStatement( $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( ! in_array( $tokens[ $endOfStatement ]['code'], array( \T_SEMICOLON, \T_CLOSE_CURLY_BRACKET ), true ) ) {
			return;
		}

		$this->process_after_keyword( $phpcsFile, $stackPtr, $tokens );
		$this->process_after_open_paren( $phpcsFile, $stackPtr, $tokens );
		$this->process_directive( $phpcsFile, $stackPtr, $tokens );
		$this->process_after_directive( $phpcsFile, $stackPtr, $tokens );
		$this->process_before_equals( $phpcsFile, $stackPtr, $tokens );
		$this->process_after_equals( $phpcsFile, $stackPtr, $tokens );
		$this->process_before_directive_value( $phpcsFile, $stackPtr, $tokens );
		$this->process_after_directive_value( $phpcsFile, $stackPtr, $tokens );
		$this->process_after_close_paren( $phpcsFile, $stackPtr, $tokens );
	}

	/**
	 * Check that there is no whitespace between the declare keyword and the opening parenthesis.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_after_keyword( File $phpcsFile, $stackPtr, array $tokens ) {
		// User is live-coding, or there is a syntax error.
		if ( ! array_key_exists( ( $stackPtr + 1 ), $tokens ) ) {
			return;
		}

		$openParen = $phpcsFile->findNext( \T_OPEN_PARENTHESIS, $stackPtr );
		$nextToken = ( $stackPtr + 1 );

		// There is no whitespace between the keyword and opening parenthesis.
		if ( $nextToken === $openParen ) {
			return;
		}

		$toReplace = array();
		for ( $i = $nextToken; $i < $openParen; $i++ ) {
			if ( \T_WHITESPACE === $tokens[ $i ]['code'] ) {
				$toReplace[] = $i;
			}
		}

		// No whitespace tokens found.
		if ( 0 === count( $toReplace ) ) {
			return;
		}

		$error = 'Expected no whitespace between declare keyword and its opening parenthesis.';
		$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'SpaceFoundAfterKeyword' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		foreach ( $toReplace as $whitespaceToken ) {
			$phpcsFile->fixer->replaceToken( $whitespaceToken, '' );
		}
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a single space after the opening parenthesis.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_after_open_paren( File $phpcsFile, $stackPtr, array $tokens ) {
		$openParen = $phpcsFile->findNext( \T_OPEN_PARENTHESIS, $stackPtr, null, false );

		// User is live-coding, or there is a syntax error.
		if ( false === $openParen ) {
			return;
		}

		$nextToken = ( $openParen + 1 );

		// We expect one space.
		if ( \T_WHITESPACE === $tokens[ $nextToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $nextToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $nextToken ]['content'], $this->lineEnding ) ) {
				$error = 'The contents of a declare statement should be single-spaced on one line.';
				$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'DirectiveOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}

				// We're done here, no need to check for space length.
				return;
			}

			if ( 1 === $spaceLength ) {
				return;
			}

			$error = 'Expected one space between opening parenthesis and directive in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $openParen, 'TooMuchSpaceFoundBeforeDirective', array( $spaceLength ) );

			if ( true !== $fix ) {
				return;
			}

			$phpcsFile->fixer->beginChangeset();
			$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
			$phpcsFile->fixer->endChangeset();

			return;
		}

		$error = 'Expected one space between opening parenthesis and directive in a declare statement; found none.';
		$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'NoSpaceFoundBeforeDirective' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContentBefore( $nextToken, ' ' );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that the directive is valid and lowercase.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_directive( File $phpcsFile, $stackPtr, array $tokens ) {
		$directive = $phpcsFile->findNext( \T_STRING, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $directive ) {
			return;
		}

		$foundDirective    = $tokens[ $directive ]['content'];
		$expectedDirective = strtolower( $foundDirective );

		// Directive is invalid; this takes precedence over casing.
		if ( ! in_array( $expectedDirective, $this->validDirectives, true ) ) {
			$error = 'Invalid directive found in declare statement; expected one of %s; found %s.';
			$phpcsFile->addError( $error, $directive, 'InvalidDirective', array( implode( ', ', $this->validDirectives ), $foundDirective ) );

			return;
		}

		if ( $foundDirective === $expectedDirective ) {
			return;
		}

		$error = 'Directives in a declare statement should be lower case; expected %s; found %s.';
		$fix   = $phpcsFile->addFixableError( $error, $directive, 'DirectiveNotLowerCase', array( $expectedDirective, $foundDirective ) );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->replaceToken( $directive, $expectedDirective );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a single space after the directive.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_after_directive( File $phpcsFile, $stackPtr, array $tokens ) {
		$directive = $phpcsFile->findNext( \T_STRING, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $directive ) {
			return;
		}

		$nextToken  = ( $directive + 1 );
		$equalsSign = $phpcsFile->findNext( \T_EQUAL, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $equalsSign ) {
			return;
		}

		// There is no content between directive and equals.
		if ( $nextToken === $equalsSign ) {
			$error = 'Expected one space between the directive and equals sign in a declare statement; found none.';
			$fix   = $phpcsFile->addFixableError( $error, $directive, 'NoSpaceFoundAfterDirective' );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContent( $directive, ' ' );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		// There is whitespace after the directive.
		if ( \T_WHITESPACE === $tokens[ $nextToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $nextToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $nextToken ]['content'], $this->lineEnding ) ) {
				$error = 'The contents of a declare statement should be single-spaced on one line.';
				$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'DirectiveOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}

				// We're done here, no need to check for space length.
				return;
			}

			if ( 1 === $spaceLength ) {
				return;
			}

			$error = 'Expected one space after the directive in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'TooMuchSpaceFoundAfterDirective', array( $spaceLength ) );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		// Non-whitespace content after the directive.
		$error = 'Expected one space after the directive in a declare statement; found none.';
		$fix   = $phpcsFile->addFixableError( $error, $directive, 'NoSpaceFoundAfterDirective' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContent( $directive, ' ' );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a single space before the equals.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_before_equals( File $phpcsFile, $stackPtr, array $tokens ) {
		$directive = $phpcsFile->findNext( \T_STRING, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $directive ) {
			return;
		}

		$equalsSign = $phpcsFile->findNext( \T_EQUAL, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $equalsSign ) {
			return;
		}

		$prevToken = ( $equalsSign - 1 );

		// There is no content between directive and equals.
		if ( $prevToken === $directive ) {
			// Whitespace addition will be handled by {process_after_directive()}.
			return;
		}

		// There is whitespace before the equals.
		if ( \T_WHITESPACE === $tokens[ $prevToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $prevToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $prevToken ]['content'], $this->lineEnding ) ) {
				$error = 'The contents of a declare statement should be single-spaced on one line.';
				$fix   = $phpcsFile->addFixableError( $error, $prevToken, 'DirectiveOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $prevToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}

				// We're done here, no need to check for space length.
				return;
			}

			if ( 1 === $spaceLength ) {
				return;
			}

			$error = 'Expected one space before the equals sign in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $prevToken, 'TooMuchSpaceFoundBeforeEquals', array( $spaceLength ) );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( $prevToken, ' ' );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		// Non-whitespace content before the equals.
		$error = 'Expected one space before the equals sign in a declare statement; found none.';
		$fix   = $phpcsFile->addFixableError( $error, $equalsSign, 'NoSpaceFoundBeforeEquals' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContentBefore( $equalsSign, ' ' );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a single space after the equals sign.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_after_equals( File $phpcsFile, $stackPtr, array $tokens ) {
		$equalsSign = $phpcsFile->findNext( \T_EQUAL, $stackPtr );

		// User is live coding, or there is a syntax error.
		if ( false === $equalsSign ) {
			return;
		}

		$nextToken = ( $equalsSign + 1 );

		// There is whitespace after the equals.
		if ( \T_WHITESPACE === $tokens[ $nextToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $nextToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $nextToken ]['content'], $this->lineEnding ) ) {
				$error = 'The contents of a declare statement should be single-spaced on one line.';
				$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'DirectiveOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}

				// We're done here, no need to check for space length.
				return;
			}

			if ( 1 === $spaceLength ) {
				return;
			}

			$error = 'Expected one space after the equals sign in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'TooMuchSpaceFoundAfterEquals', array( $spaceLength ) );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		// Non-whitespace content after the equals.
		$error = 'Expected one space after the equals sign in a declare statement; found none.';
		$fix   = $phpcsFile->addFixableError( $error, $equalsSign, 'NoSpaceFoundAfterEquals' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContent( $equalsSign, ' ' );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a single space before the directive value.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_before_directive_value( File $phpcsFile, $stackPtr, array $tokens ) {
		$equalsSign = $phpcsFile->findNext( \T_EQUAL, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $equalsSign ) {
			return;
		}

		$directiveValue = $phpcsFile->findNext( array( \T_STRING, \T_LNUMBER ), $equalsSign );

		// User is live coding, or there is a syntax error.
		if ( false === $directiveValue ) {
			return;
		}

		$prevToken = ( $directiveValue - 1 );

		// There is no content between the equals and directive value.
		if ( $prevToken === $equalsSign ) {
			// Whitespace addition will be handled by {process_after_equals()}.
			return;
		}

		// There is whitespace before the directive value.
		if ( \T_WHITESPACE === $tokens[ $prevToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $prevToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $prevToken ]['content'], $this->lineEnding ) ) {
				$error = 'The contents of a declare statement should be single-spaced on one line.';
				$fix   = $phpcsFile->addFixableError( $error, $prevToken, 'DirectiveOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $prevToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}

				// We're done here, no need to check for space length.
				return;
			}

			if ( 1 === $spaceLength ) {
				return;
			}

			$error = 'Expected one space before the directive value in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $directiveValue, 'TooMuchSpaceFoundBeforeDirectiveValue', array( $spaceLength ) );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( $prevToken, ' ' );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		// Non-whitespace content before the directive value.
		$error = 'Expected one space before the directive value in a declare statement; found none.';
		$fix   = $phpcsFile->addFixableError( $error, $directiveValue, 'NoSpaceFoundBeforeDirectiveValue' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContentBefore( $directiveValue, ' ' );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a single space between directive value and closing parenthesis.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_after_directive_value( File $phpcsFile, $stackPtr, array $tokens ) {
		$equalsSign = $phpcsFile->findNext( \T_EQUAL, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $equalsSign ) {
			return;
		}

		$directiveValue = $phpcsFile->findNext( \T_WHITESPACE, ( $equalsSign + 1 ), null, true );

		// User is live-coding, or there is a syntax error.
		if ( false === $directiveValue ) {
			return;
		}

		$nextToken = ( $directiveValue + 1 );

		// We expect a whitespace character.
		if ( \T_WHITESPACE === $tokens[ $nextToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $nextToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $nextToken ]['content'], $this->lineEnding ) ) {
				$error = 'The contents of a declare statement should be single-spaced on one line.';
				$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'DirectiveOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}

				// We're done here, no need to check for space length.
				return;
			}

			if ( 1 === $spaceLength ) {
				return;
			}

			$error = 'Expected one space after the directive value in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'TooMuchSpaceFoundAfterDirectiveValue', array( $spaceLength ) );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		// Non-whitespace content after the directive value.
		$error = 'Expected one space after the directive value in a declare statement; found none.';
		$fix   = $phpcsFile->addFixableError( $error, $directiveValue, 'NoSpaceFoundAfterDirectiveValue' );

		if ( true !== $fix ) {
			return;
		}

		$phpcsFile->fixer->beginChangeset();
		$phpcsFile->fixer->addContent( $directiveValue, ' ' );
		$phpcsFile->fixer->endChangeset();
	}

	/**
	 * Check that there is a semi-colon or curly braces after the closing parenthesis.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_after_close_paren( File $phpcsFile, $stackPtr, array $tokens ) {
		$closingParen = $phpcsFile->findNext( \T_CLOSE_PARENTHESIS, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $closingParen ) {
			return;
		}

		$nextToken = ( $closingParen + 1 );

		// Statement is termninated with semi-colon.
		if ( \T_SEMICOLON === $tokens[ $nextToken ]['code'] ) {
			return;
		}

		// There is whitespace after the directive value, and then it terminates.
		if ( \T_WHITESPACE === $tokens[ $nextToken ]['code'] && \T_SEMICOLON === $tokens[ ( $nextToken + 1 ) ]['code'] ) {
			$spaceLength = strlen( $tokens[ $nextToken ]['content'] );

			$error = 'Expected no space between the closing parenthesis and semi-colon in a declare statement; found %s.';
			$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'SpaceFoundAfterClosingParen', array( $spaceLength ) );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( $nextToken, '' );
				$phpcsFile->fixer->endChangeset();
			}

			// We can bail here because the statement terminates, and we need not do further checking.
			return;
		}

		$this->process_block_statement( $phpcsFile, $stackPtr, $tokens );
	}

	/**
	 * Process block statement braces.
	 *
	 * @param \PHP_CodeSniffer\Files\File    $phpcsFile The file being scanned.
	 * @param int                            $stackPtr  The position of the current token in
	 *                                                  the stack passed in $tokens.
	 * @param array<int,array<string,mixed>> $tokens    All found tokens in the file.
	 *
	 * @return void
	 */
	protected function process_block_statement( File $phpcsFile, $stackPtr, array $tokens ) {
		$closingParen = $phpcsFile->findNext( \T_CLOSE_PARENTHESIS, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $closingParen ) {
			return;
		}

		$openingBrace = $phpcsFile->findNext( \T_OPEN_CURLY_BRACKET, $stackPtr );

		// User is live-coding, or there is a syntax error.
		if ( false === $openingBrace ) {
			return;
		}

		$nextToken = ( $closingParen + 1 );

		// We expect a single space between the closing paren and opening curly brace.
		if ( \T_WHITESPACE === $tokens[ $nextToken ]['code'] ) {
			$spaceLength = strlen( $tokens[ $nextToken ]['content'] );

			// And no newlines!
			if ( false !== strpos( $tokens[ $nextToken ]['content'], $this->lineEnding ) ) {
				$error = 'The opening curly brace should be on the same line as the declare keyword.';
				$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'OpeningCurlyBraceOnNewLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			}

			if ( 1 !== $spaceLength ) {
				$error = 'Expected one space between the closing parenthesis and opening curly brace in a declare statement; found %s.';
				$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'TooMuchSpaceFoundAfterClosingParen', array( $spaceLength ) );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( $nextToken, ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			}
		}

		// There is no whitespace.
		if ( \T_OPEN_CURLY_BRACKET === $tokens[ $nextToken ]['code'] ) {
			$error = 'Expected one space between the closing parenthesis and curly brace in a declare statement; found none.';
			$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'NoSpaceFoundAfterClosingParen' );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContentBefore( $nextToken, ' ' );
				$phpcsFile->fixer->endChangeset();
			}
		}

		$openingBrace = $phpcsFile->findNext( \T_OPEN_CURLY_BRACKET, $stackPtr );
		$nextToken    = $phpcsFile->findNext( Tokens::$emptyTokens, ( $openingBrace + 1 ), null, true );

		// The opening curly brace is not the last content on the line.
		if ( $nextToken && $tokens[ $openingBrace ]['line'] === $tokens[ $nextToken ]['line'] && \T_CLOSE_CURLY_BRACKET !== $tokens[ $nextToken ]['code'] ) {
			$error = 'The opening curly brace should be the last content on a line in a declare statement.';
			$fix   = $phpcsFile->addFixableError( $error, $nextToken, 'ContentFoundAfterOpeningCurlyBrace' );

			if ( true === $fix ) {
				$prevToken = $phpcsFile->findPrevious( \T_WHITESPACE, ( $nextToken - 1 ), null, true );

				$phpcsFile->fixer->beginChangeset();
				for ( $i = ( $prevToken + 1 ); $i < $nextToken; $i++ ) {
					$phpcsFile->fixer->replaceToken( $i, '' );
				}
				$phpcsFile->fixer->addNewlineBefore( $nextToken );
				$phpcsFile->fixer->endChangeset();
			}
		}

		// User is live-coding, or there is a syntax error.
		if ( ! isset( $tokens[ $tokens[ $openingBrace ]['bracket_closer'] ] ) ) {
			return;
		}

		$closingBrace = $tokens[ $openingBrace ]['bracket_closer'];

		// Closing brace is aligned with declare keyword.
		if ( $tokens[ $closingBrace ]['column'] === $tokens[ $stackPtr ]['column'] ) {
			return;
		}

		if ( $tokens[ $openingBrace ]['line'] === $tokens[ $closingBrace ]['line'] ) {
			$error = 'The closing brace of a declare statement should be on a new line.';
			$fix   = $phpcsFile->addFixableError( $error, $closingBrace, 'ClosingBraceWrongLine' );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContentBefore( $closingBrace, $this->lineEnding );
				$phpcsFile->fixer->endChangeset();
			}

			return;
		}

		$error = 'The closing brace of a declare statement should be aligned with the declare keyword.';
		$phpcsFile->addError( $error, $closingBrace, 'ClosingBraceNotAligned' );
	}
}
