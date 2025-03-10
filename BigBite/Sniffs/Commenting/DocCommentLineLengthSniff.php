<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Restricts max line length in block comments
 */
final class DocCommentLineLengthSniff implements Sniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array<int,string>
	 */
	public $supportedTokenizers = array(
		'PHP',
	);

	/**
	 * The limit that the length of a line should not exceed.
	 *
	 * @var int
	 */
	public $lineLimit = 80;

	/**
	 * The limit that the length of a line must not exceed.
	 *
	 * Set to zero (0) to disable.
	 *
	 * @var int
	 */
	public $absoluteLineLimit = 100;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int,string>
	 */
	public function register() {
		return array( \T_DOC_COMMENT_OPEN_TAG );
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

		if ( false === isset( $tokens[ $stackPtr ]['comment_closer'] )
			|| ( '' === $tokens[ $tokens[ $stackPtr ]['comment_closer'] ]['content']
			&& ( $phpcsFile->numTokens - 1 ) === $tokens[ $stackPtr ]['comment_closer'] )
		) {
			// Don't process an unfinished comment during live coding.
			return;
		}

		$commentStart = $stackPtr;
		$commentEnd   = $tokens[ $stackPtr ]['comment_closer'];

		$empty = array(
			\T_DOC_COMMENT_WHITESPACE,
			\T_DOC_COMMENT_STAR,
		);

		$short = $phpcsFile->findNext( $empty, ( $stackPtr + 1 ), $commentEnd, true );
		if ( false === $short ) {
			// No content at all.
			return;
		}

		if ( 1 === preg_match( '/^(Plugin|Theme)\s+Name:/i', $tokens[ $short ]['content'] ) ) {
			// It's a WordPress plugin or theme, so disregard line length.
			return;
		}

		for ( $i = ( $commentStart + 1 ); $i < $commentEnd; $i++ ) {
			if ( \T_DOC_COMMENT_STRING !== $tokens[ $i ]['code'] ) {
				continue;
			}

			$lineLength = ( $tokens[ $i ]['column'] + $tokens[ $i ]['length'] - 1 );

			// Record metrics.
			if ( $lineLength <= $this->lineLimit ) {
				$phpcsFile->recordMetric( $i, 'Line length', "{$this->lineLimit} or less" );
			} elseif ( $lineLength <= $this->absoluteLineLimit ) {
				$phpcsFile->recordMetric( $i, 'Line length', "{$this->absoluteLineLimit} or less" );
			} else {
				$phpcsFile->recordMetric( $i, 'Line length', "more than {$this->absoluteLineLimit}" );
			}

			// If this is a long comment, check if it can be broken up onto multiple lines.
			// Some comments contain unbreakable strings like URLs and so it makes sense
			// to ignore the line length in these cases if the URL would be longer than the max
			// line length once you indent it to the correct level.
			if ( $lineLength > $this->lineLimit ) {
				$oldLength = strlen( $tokens[ $i ]['content'] );
				$newLength = strlen( ltrim( $tokens[ $i ]['content'], "/#\t " ) );
				$indent    = ( ( $tokens[ $i ]['column'] - 1 ) + ( $oldLength - $newLength ) );

				$nonBreakingLength = $tokens[ $i ]['length'];

				$space = strrpos( $tokens[ $i ]['content'], ' ' );
				if ( false !== $space ) {
					$nonBreakingLength -= ( $space + 1 );
				}

				if ( ( $nonBreakingLength + $indent ) > $this->lineLimit ) {
					return;
				}
			}

			if ( $this->absoluteLineLimit > 0 && $lineLength > $this->absoluteLineLimit ) {
				$data = array(
					$this->absoluteLineLimit,
					$lineLength,
				);

				$error = 'Line exceeds maximum limit of %s characters; contains %s characters';
				$phpcsFile->addError( $error, $i, 'MaxExceeded', $data );
			} elseif ( $lineLength > $this->lineLimit ) {
				$data = array(
					$this->lineLimit,
					$lineLength,
				);

				$warning = 'Line exceeds %s characters; contains %s characters';
				$phpcsFile->addWarning( $warning, $i, 'TooLong', $data );
			}
		}
	}
}
