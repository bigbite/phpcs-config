<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBiteDocs
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBiteDocs\Sniffs\Commenting;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentSniff as SquizFunctionCommentSniff;

/**
 * Parses and verifies the doc comments for functions.
 */
final class FunctionCommentSniff extends SquizFunctionCommentSniff {

	/**
	 * An array of variable types for param/var we will check.
	 *
	 * @var array<int,string>
	 */
	protected static $allowedTypes = array(
		'array',
		'bool',
		'float',
		'int',
		'mixed',
		'object',
		'string',
		'resource',
		'callable',
	);

	/**
	 * The current PHP version.
	 *
	 * @var int|string|null
	 */
	private $phpVersion = null;

	/**
	 * Process the return comment of this function comment.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processReturn( File $phpcsFile, $stackPtr, $commentStart ) {
		$tokens = $phpcsFile->getTokens();
		$return = null;

		if ( true === $this->skipIfInheritdoc ) {
			if ( true === $this->checkInheritdoc( $phpcsFile, $stackPtr, $commentStart ) ) {
				return;
			}
		}

		foreach ( $tokens[ $commentStart ]['comment_tags'] as $tag ) {
			if ( '@return' === $tokens[ $tag ]['content'] ) {
				if ( null !== $return ) {
					$error = 'Only 1 @return tag is allowed in a function comment';
					$phpcsFile->addError( $error, $tag, 'DuplicateReturn' );
					return;
				}

				$return = $tag;
			}
		}

		// Skip constructor and destructor.
		$methodName      = $phpcsFile->getDeclarationName( $stackPtr );
		$isSpecialMethod = in_array( $methodName, $this->specialMethods, true );

		if ( null !== $return ) {
			$content = $tokens[ ( $return + 2 ) ]['content'];
			if ( empty( $content ) === true || T_DOC_COMMENT_STRING !== $tokens[ ( $return + 2 ) ]['code'] ) {
				$error = 'Return type missing for @return tag in function comment';
				$phpcsFile->addError( $error, $return, 'MissingReturnType' );
			} else {
				// Support both a return type and a description.
				preg_match( '`^((?:\|?(?:array\([^\)]*\)|[\\\\a-z0-9\[\]]+))*)( .*)?`i', $content, $returnParts );
				if ( isset( $returnParts[1] ) === false ) {
					return;
				}

				$returnType = $returnParts[1];

				// Check return type (can be multiple, separated by '|').
				$typeNames      = explode( '|', $returnType );
				$suggestedNames = array();
				foreach ( $typeNames as $typeName ) {
					$suggestedName = $this->suggestType( $typeName );
					if ( in_array( $suggestedName, $suggestedNames, true ) === false ) {
						$suggestedNames[] = $suggestedName;
					}
				}

				$suggestedType = implode( '|', $suggestedNames );
				if ( $returnType !== $suggestedType ) {
					$error = 'Expected "%s" but found "%s" for function return type';
					$data  = array(
						$suggestedType,
						$returnType,
					);

					$fix = $phpcsFile->addFixableError( $error, $return, 'InvalidReturn', $data );

					if ( true === $fix ) {
						$replacement = $suggestedType;
						if ( empty( $returnParts[2] ) === false ) {
							$replacement .= $returnParts[2];
						}

						$phpcsFile->fixer->replaceToken( ( $return + 2 ), $replacement );
						unset( $replacement );
					}
				}

				// If the return type is void, make sure there is
				// no return statement in the function.
				if ( 'void' === $returnType ) {
					if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === true ) {
						$endToken = $tokens[ $stackPtr ]['scope_closer'];
						for ( $returnToken = $stackPtr; $returnToken < $endToken; $returnToken++ ) {
							if ( T_CLOSURE === $tokens[ $returnToken ]['code']
								|| T_ANON_CLASS === $tokens[ $returnToken ]['code']
							) {
								$returnToken = $tokens[ $returnToken ]['scope_closer'];
								continue;
							}

							if ( T_RETURN === $tokens[ $returnToken ]['code']
								|| T_YIELD === $tokens[ $returnToken ]['code']
								|| T_YIELD_FROM === $tokens[ $returnToken ]['code']
							) {
								break;
							}
						}

						if ( $returnToken !== $endToken ) {
							// If the function is not returning anything, just
							// exiting, then there is no problem.
							$semicolon = $phpcsFile->findNext( T_WHITESPACE, ( $returnToken + 1 ), null, true );
							if ( T_SEMICOLON !== $tokens[ $semicolon ]['code'] ) {
								$error = 'Function return type is void, but function contains return statement';
								$phpcsFile->addError( $error, $return, 'InvalidReturnVoid' );
							}
						}
					}
				} elseif ( 'mixed' !== $returnType
					&& 'never' !== $returnType
					&& in_array( 'void', $typeNames, true ) === false
				) {
					// If return type is not void, never, or mixed, there needs to be a
					// return statement somewhere in the function that returns something.
					if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === true ) {
						$endToken = $tokens[ $stackPtr ]['scope_closer'];
						for ( $returnToken = $stackPtr; $returnToken < $endToken; $returnToken++ ) {
							if ( T_CLOSURE === $tokens[ $returnToken ]['code']
								|| T_ANON_CLASS === $tokens[ $returnToken ]['code']
							) {
								$returnToken = $tokens[ $returnToken ]['scope_closer'];
								continue;
							}

							if ( T_RETURN === $tokens[ $returnToken ]['code']
								|| T_YIELD === $tokens[ $returnToken ]['code']
								|| T_YIELD_FROM === $tokens[ $returnToken ]['code']
							) {
								break;
							}
						}

						if ( $returnToken === $endToken ) {
							$error = 'Function return type is not void, but function has no return statement';
							$phpcsFile->addError( $error, $return, 'InvalidNoReturn' );
						} else {
							$semicolon = $phpcsFile->findNext( T_WHITESPACE, ( $returnToken + 1 ), null, true );
							if ( T_SEMICOLON === $tokens[ $semicolon ]['code'] ) {
								$error = 'Function return type is not void, but function is returning void here';
								$phpcsFile->addError( $error, $returnToken, 'InvalidReturnNotVoid' );
							}
						}
					}
				}
			}
		} else {
			if ( true === $isSpecialMethod ) {
				return;
			}

			$error = 'Missing @return tag in function comment';
			$phpcsFile->addError( $error, $tokens[ $commentStart ]['comment_closer'], 'MissingReturn' );
		}
	}

	/**
	 * Process any throw tags that this function comment has.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processThrows( File $phpcsFile, $stackPtr, $commentStart ) {
		$tokens = $phpcsFile->getTokens();

		if ( true === $this->skipIfInheritdoc ) {
			if ( true === $this->checkInheritdoc( $phpcsFile, $stackPtr, $commentStart ) ) {
				return;
			}
		}

		foreach ( $tokens[ $commentStart ]['comment_tags'] as $pos => $tag ) {
			if ( '@throws' !== $tokens[ $tag ]['content'] ) {
				continue;
			}

			$exception = null;
			$comment   = null;
			if ( T_DOC_COMMENT_STRING === $tokens[ ( $tag + 2 ) ]['code'] ) {
				$matches = array();
				preg_match( '/([^\s]+)(?:\s+(.*))?/', $tokens[ ( $tag + 2 ) ]['content'], $matches );
				$exception = $matches[1];
				if ( true === isset( $matches[2] ) && '' !== trim( $matches[2] ) ) {
					$comment = $matches[2];
				}
			}

			if ( null === $exception ) {
				$error = 'Exception type and comment missing for @throws tag in function comment';
				$phpcsFile->addError( $error, $tag, 'InvalidThrows' );
			} elseif ( null === $comment ) {
				$error = 'Comment missing for @throws tag in function comment';
				$phpcsFile->addError( $error, $tag, 'EmptyThrows' );
			} else {
				// Any strings until the next tag belong to this comment.
				if ( true === isset( $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ] ) ) {
					$end = $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ];
				} else {
					$end = $tokens[ $commentStart ]['comment_closer'];
				}

				for ( $i = ( $tag + 3 ); $i < $end; $i++ ) {
					if ( T_DOC_COMMENT_STRING === $tokens[ $i ]['code'] ) {
						$comment .= ' ' . $tokens[ $i ]['content'];
					}
				}

				$comment = trim( $comment );

				// Starts with a capital letter and ends with a fullstop.
				$firstChar = $comment[0];
				if ( strtoupper( $firstChar ) !== $firstChar ) {
					$error = '@throws tag comment must start with a capital letter';
					$fix   = $phpcsFile->addFixableError( $error, ( $tag + 2 ), 'ThrowsNotCapital' );

					if ( true === $fix ) {
						$oldContent  = $comment;
						$newContent  = ucfirst( $oldContent );
						$replacement = str_replace( $oldContent, $newContent, $tokens[ ( $tag + 2 ) ]['content'] );

						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->replaceToken( ( $tag + 2 ), $replacement );
						$phpcsFile->fixer->endChangeset();
						return;
					}
				}

				$lastChar = substr( $comment, -1 );
				if ( '.' !== $lastChar ) {
					$error = '@throws tag comment must end with a full stop';
					$fix   = $phpcsFile->addFixableError( $error, ( $tag + 2 ), 'ThrowsNoFullStop' );

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->addContent( ( $tag + 2 ), '.' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			}
		}
	}

	/**
	 * Process the function parameter comments.
	 *
	 * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
	 * @param int                         $stackPtr     The position of the current token
	 *                                                  in the stack passed in $tokens.
	 * @param int                         $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processParams( File $phpcsFile, $stackPtr, $commentStart ) {
		if ( null === $this->phpVersion ) {
			$this->phpVersion = Config::getConfigData( 'php_version' );

			if ( null === $this->phpVersion ) {
				$this->phpVersion = PHP_VERSION_ID;
			}
		}

		$tokens = $phpcsFile->getTokens();

		if ( true === $this->skipIfInheritdoc ) {
			if ( true === $this->checkInheritdoc( $phpcsFile, $stackPtr, $commentStart ) ) {
				return;
			}
		}

		$params  = array();
		$maxType = 0;
		$maxVar  = 0;

		foreach ( $tokens[ $commentStart ]['comment_tags'] as $pos => $tag ) {
			if ( '@param' !== $tokens[ $tag ]['content'] ) {
				continue;
			}

			$type         = '';
			$typeSpace    = 0;
			$var          = '';
			$varSpace     = 0;
			$comment      = '';
			$commentLines = array();
			if ( T_DOC_COMMENT_STRING === $tokens[ ( $tag + 2 ) ]['code'] ) {
				$matches = array();
				preg_match( '/([^$&.]+)(?:((?:\.\.\.)?(?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[ ( $tag + 2 ) ]['content'], $matches );

				if ( empty( $matches ) === false ) {
					$typeLen   = strlen( $matches[1] );
					$type      = trim( $matches[1] );
					$typeSpace = ( $typeLen - strlen( $type ) );
					$typeLen   = strlen( $type );
					if ( $typeLen > $maxType ) {
						$maxType = $typeLen;
					}
				}

				if ( isset( $matches[2] ) === true ) {
					$var    = $matches[2];
					$varLen = strlen( $var );
					if ( $varLen > $maxVar ) {
						$maxVar = $varLen;
					}

					if ( isset( $matches[4] ) === true ) {
						$varSpace       = strlen( $matches[3] );
						$comment        = $matches[4];
						$commentLines[] = array(
							'comment' => $comment,
							'token'   => ( $tag + 2 ),
							'indent'  => $varSpace,
						);

						// Any strings until the next tag belong to this comment.
						if ( isset( $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ] ) === true ) {
							$end = $tokens[ $commentStart ]['comment_tags'][ ( $pos + 1 ) ];
						} else {
							$end = $tokens[ $commentStart ]['comment_closer'];
						}

						for ( $i = ( $tag + 3 ); $i < $end; $i++ ) {
							if ( T_DOC_COMMENT_STRING === $tokens[ $i ]['code'] ) {
								$indent = 0;
								if ( T_DOC_COMMENT_WHITESPACE === $tokens[ ( $i - 1 ) ]['code'] ) {
									$indent = $tokens[ ( $i - 1 ) ]['length'];
								}

								$comment       .= ' ' . $tokens[ $i ]['content'];
								$commentLines[] = array(
									'comment' => $tokens[ $i ]['content'],
									'token'   => $i,
									'indent'  => $indent,
								);
							}
						}
					} else {
						$error = 'Missing parameter comment';
						$phpcsFile->addError( $error, $tag, 'MissingParamComment' );
						$commentLines[] = array( 'comment' => '' );
					}
				} elseif ( '$' === $tokens[ ( $tag + 2 ) ]['content'][0] ) {
					$error = 'Missing parameter type';
					$phpcsFile->addError( $error, $tag, 'MissingParamType' );
				} else {
					$error = 'Missing parameter name';
					$phpcsFile->addError( $error, $tag, 'MissingParamName' );
				}
			} else {
				$error = 'Missing parameter type';
				$phpcsFile->addError( $error, $tag, 'MissingParamType' );
			}

			$params[] = array(
				'tag'          => $tag,
				'type'         => $type,
				'var'          => $var,
				'comment'      => $comment,
				'commentLines' => $commentLines,
				'type_space'   => $typeSpace,
				'var_space'    => $varSpace,
			);
		}

		$realParams  = $phpcsFile->getMethodParameters( $stackPtr );
		$foundParams = array();

		// We want to use ... for all variable length arguments, so added
		// this prefix to the variable name so comparisons are easier.
		foreach ( $realParams as $pos => $param ) {
			if ( true === $param['variable_length'] ) {
				$realParams[ $pos ]['name'] = '...' . $realParams[ $pos ]['name'];
			}
		}

		foreach ( $params as $pos => $param ) {
			// If the type is empty, the whole line is empty.
			if ( '' === $param['type'] ) {
				continue;
			}

			// Check the param type value.
			$typeNames          = explode( '|', $param['type'] );
			$suggestedTypeNames = array();

			foreach ( $typeNames as $typeName ) {
				if ( '' === $typeName ) {
					continue;
				}

				// Strip nullable operator.
				if ( '?' === $typeName[0] ) {
					$typeName = substr( $typeName, 1 );
				}

				$suggestedName        = $this->suggestType( $typeName );
				$suggestedTypeNames[] = $suggestedName;

				if ( count( $typeNames ) > 1 ) {
					continue;
				}

				// Check type hint for array and custom type.
				$suggestedTypeHint = '';
				if ( false !== strpos( $suggestedName, 'array' ) || '[]' === substr( $suggestedName, -2 ) ) {
					$suggestedTypeHint = 'array';
				} elseif ( false !== strpos( $suggestedName, 'callable' ) ) {
					$suggestedTypeHint = 'callable';
				} elseif ( false !== strpos( $suggestedName, 'callback' ) ) {
					$suggestedTypeHint = 'callable';
				} elseif ( false === in_array( $suggestedName, $this::$allowedTypes, true ) ) {
					$suggestedTypeHint = $suggestedName;
				}

				if ( $this->phpVersion >= 70000 ) {
					if ( 'string' === $suggestedName ) {
						$suggestedTypeHint = 'string';
					} elseif ( 'int' === $suggestedName || 'integer' === $suggestedName ) {
						$suggestedTypeHint = 'int';
					} elseif ( 'float' === $suggestedName ) {
						$suggestedTypeHint = 'float';
					} elseif ( 'bool' === $suggestedName || 'boolean' === $suggestedName ) {
						$suggestedTypeHint = 'bool';
					}
				}

				if ( $this->phpVersion >= 70200 ) {
					if ( 'object' === $suggestedName ) {
						$suggestedTypeHint = 'object';
					}
				}

				if ( $this->phpVersion >= 80000 ) {
					if ( 'mixed' === $suggestedName ) {
						$suggestedTypeHint = 'mixed';
					}
				}

				if ( '' !== $suggestedTypeHint && true === isset( $realParams[ $pos ] ) && '' !== $param['var'] ) {
					$typeHint = $realParams[ $pos ]['type_hint'];

					// Remove namespace prefixes when comparing.
					$compareTypeHint = substr( $suggestedTypeHint, ( strlen( $typeHint ) * -1 ) );

					if ( '' === $typeHint ) {
						$error = 'Type hint "%s" missing for %s';
						$data  = array(
							$suggestedTypeHint,
							$param['var'],
						);

						$errorCode = 'TypeHintMissing';
						if ( 'string' === $suggestedTypeHint
							|| 'int' === $suggestedTypeHint
							|| 'float' === $suggestedTypeHint
							|| 'bool' === $suggestedTypeHint
						) {
							$errorCode = 'Scalar' . $errorCode;
						}

						$phpcsFile->addError( $error, $stackPtr, $errorCode, $data );
					} elseif ( $typeHint !== $compareTypeHint && '?' !== $typeHint . $compareTypeHint ) {
						$error = 'Expected type hint "%s"; found "%s" for %s';
						$data  = array(
							$suggestedTypeHint,
							$typeHint,
							$param['var'],
						);
						$phpcsFile->addError( $error, $stackPtr, 'IncorrectTypeHint', $data );
					}
				} elseif ( '' === $suggestedTypeHint && true === isset( $realParams[ $pos ] ) ) {
					$typeHint = $realParams[ $pos ]['type_hint'];
					if ( '' !== $typeHint ) {
						$error = 'Unknown type hint "%s" found for %s';
						$data  = array(
							$typeHint,
							$param['var'],
						);
						$phpcsFile->addError( $error, $stackPtr, 'InvalidTypeHint', $data );
					}
				}
			}

			$suggestedType = implode( '|', $suggestedTypeNames );
			if ( $param['type'] !== $suggestedType ) {
				$error = 'Expected "%s" but found "%s" for parameter type';
				$data  = array(
					$suggestedType,
					$param['type'],
				);

				$fix = $phpcsFile->addFixableError( $error, $param['tag'], 'IncorrectParamVarName', $data );
				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();

					$content  = $suggestedType;
					$content .= str_repeat( ' ', $param['type_space'] );
					$content .= $param['var'];
					$content .= str_repeat( ' ', $param['var_space'] );
					if ( isset( $param['commentLines'][0] ) === true ) {
						$content .= $param['commentLines'][0]['comment'];
					}

					$phpcsFile->fixer->replaceToken( ( $param['tag'] + 2 ), $content );

					// Fix up the indent of additional comment lines.
					foreach ( $param['commentLines'] as $lineNum => $line ) {
						if ( 0 === $lineNum || 0 === $param['commentLines'][ $lineNum ]['indent'] ) {
							continue;
						}

						$diff      = ( strlen( $param['type'] ) - strlen( $suggestedType ) );
						$newIndent = ( $param['commentLines'][ $lineNum ]['indent'] - $diff );

						$phpcsFile->fixer->replaceToken(
							( $param['commentLines'][ $lineNum ]['token'] - 1 ),
							str_repeat( ' ', $newIndent )
						);
					}

					$phpcsFile->fixer->endChangeset();
				}
			}

			if ( '' === $param['var'] ) {
				continue;
			}

			$foundParams[] = $param['var'];

			// Check number of spaces after the type.
			$this->checkSpacingAfterParamType( $phpcsFile, $param, $maxType );

			// Make sure the param name is correct.
			if ( true === isset( $realParams[ $pos ] ) ) {
				$realName     = $realParams[ $pos ]['name'];
				$paramVarName = $param['var'];

				if ( '&' === $param['var'][0] ) {
					// Even when passed by reference, the variable name in $realParams does not have
					// a leading '&'. This sniff will accept both '&$var' and '$var' in these cases.
					$paramVarName = substr( $param['var'], 1 );

					// This makes sure that the 'MissingParamTag' check won't throw a false positive.
					$foundParams[ ( count( $foundParams ) - 1 ) ] = $paramVarName;

					if ( true !== $realParams[ $pos ]['pass_by_reference'] && $realName === $paramVarName ) {
						// Don't complain about this unless the param name is otherwise correct.
						$error = 'Doc comment for parameter %s is prefixed with "&" but parameter is not passed by reference';
						$code  = 'ParamNameUnexpectedAmpersandPrefix';
						$data  = array( $paramVarName );

						// We're not offering an auto-fix here because we can't tell if the docblock
						// is wrong, or the parameter should be passed by reference.
						$phpcsFile->addError( $error, $param['tag'], $code, $data );
					}
				}

				if ( $realName !== $paramVarName ) {
					$code = 'ParamNameNoMatch';
					$data = array(
						$paramVarName,
						$realName,
					);

					$error = 'Doc comment for parameter %s does not match ';
					if ( strtolower( $paramVarName ) === strtolower( $realName ) ) {
						$error .= 'case of ';
						$code   = 'ParamNameNoCaseMatch';
					}

					$error .= 'actual variable name %s';

					$phpcsFile->addError( $error, $param['tag'], $code, $data );
				}
			} elseif ( substr( $param['var'], -4 ) !== ',...' ) {
				// We must have an extra parameter comment.
				$error = 'Superfluous parameter comment';
				$phpcsFile->addError( $error, $param['tag'], 'ExtraParamComment' );
			}

			if ( '' === $param['comment'] ) {
				continue;
			}

			// Check number of spaces after the var name.
			$this->checkSpacingAfterParamName( $phpcsFile, $param, $maxVar );

			// Param comments must start with a capital letter and end with a full stop.
			if ( 1 === preg_match( '/^(\p{Ll}|\P{L})/u', $param['comment'] ) ) {
				$error = 'Parameter comment must start with a capital letter';

				$firstCharOrd = ord( $param['comment'][0] );

				// Char is lowercase ASCII letter - we can fix it.
				if ( $firstCharOrd >= 97 && $firstCharOrd <= 122 ) {
					$fix = $phpcsFile->addFixableError( $error, $param['tag'], 'ParamCommentNotCapital', array(), 0 );

					if ( true === $fix ) {
						$tokenToFix  = ( $param['tag'] + 2 );
						$oldContent  = $param['commentLines'][0]['comment'];
						$newContent  = ucfirst( $oldContent );
						$replacement = str_replace( $oldContent, $newContent, $tokens[ $tokenToFix ]['content'] );

						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->replaceToken( $tokenToFix, $replacement );
						$phpcsFile->fixer->endChangeset();
					}
				} else {
					// Char is not lowercase ASCII letter - user must manually fix.
					$phpcsFile->addError( $error, $param['tag'], 'ParamCommentNotCapital', array(), 0 );
				}
			}

			$lastChar = substr( $param['comment'], -1 );
			if ( '.' !== $lastChar ) {
				$error = 'Parameter comment must end with a full stop';
				$fix   = $phpcsFile->addFixableError( $error, $param['tag'], 'ParamCommentFullStop', array(), 0 );

				if ( true === $fix ) {
					$lineCount = count( $param['commentLines'] );

					if ( 1 === $lineCount ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->addContent( ( $param['tag'] + 2 ), '.' );
						$phpcsFile->fixer->endChangeset();
					} else {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->addContent( $param['commentLines'][ ( $lineCount - 1 ) ]['token'], '.' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			}
		}

		$realNames = array();
		foreach ( $realParams as $realParam ) {
			$realNames[] = $realParam['name'];
		}

		// Report missing comments.
		$diff = array_diff( $realNames, $foundParams );
		foreach ( $diff as $neededParam ) {
			$error = 'Doc comment for parameter "%s" missing';
			$data  = array( $neededParam );
			$phpcsFile->addError( $error, $commentStart, 'MissingParamTag', $data );
		}
	}

	/**
	 * Returns a valid variable type for param/var tags.
	 *
	 * If type is not one of the standard types, it must be a custom type.
	 * Returns the correct type name suggestion if type name is invalid.
	 *
	 * @param string $varType The variable type to process.
	 *
	 * @return string
	 */
	protected function suggestType( $varType ) {
		if ( '' === $varType ) {
			return '';
		}

		// A valid type.
		if ( in_array( $varType, self::$allowedTypes, true ) === true ) {
			return $varType;
		}

		$lowerVarType = strtolower( $varType );
		switch ( $lowerVarType ) {
			case 'bool':
			case 'boolean':
				return 'bool';
			case 'double':
			case 'real':
			case 'float':
				return 'float';
			case 'int':
			case 'integer':
				return 'int';
			case 'array()':
			case 'array':
				return 'array';
		}

		// A valid type, but not lower cased.
		if ( in_array( $lowerVarType, self::$allowedTypes, true ) === true ) {
			return $lowerVarType;
		}

		// Old-style array declaration.
		if ( strpos( $lowerVarType, 'array(' ) !== false ) {
			// Valid array declaration:
			// array, array<type>, array<type1,type2>.
			$matches = array();
			$pattern = '/^array\(\s*([^\s^=^>]*)(\s*=>\s*(.*))?\s*\)/i';

			if ( preg_match( $pattern, $varType, $matches ) !== 0 ) {
				$type1 = '';
				if ( isset( $matches[1] ) === true ) {
					$type1 = $matches[1];
				}

				$type2 = '';
				if ( isset( $matches[3] ) === true ) {
					$type2 = $matches[3];
				}

				$type1 = $this->suggestType( $type1 );
				$type2 = $this->suggestType( $type2 );
				if ( '' !== $type2 ) {
					$type2 = ',' . $type2;
				}

				return "array<$type1$type2>";
			}

			return 'array';
		}

		// New style array declaration.
		if ( strpos( $lowerVarType, 'array<' ) !== false ) {
			// Valid array declaration:
			// array, array<type>, array<type1,type2>.
			$matches = array();
			$pattern = '/^array\<\s*([^\s^,]*)(\s*,\s*(.*))?\s*\>/i';

			if ( preg_match( $pattern, $varType, $matches ) !== 0 ) {
				$type1 = '';
				if ( isset( $matches[1] ) === true ) {
					$type1 = $matches[1];
				}

				$type2 = '';
				if ( isset( $matches[3] ) === true ) {
					$type2 = $matches[3];
				}

				$type1 = $this->suggestType( $type1 );
				$type2 = $this->suggestType( $type2 );
				if ( '' !== $type2 ) {
					$type2 = ',' . $type2;
				}

				return "array<$type1$type2>";
			}

			return 'array';
		}

		// Must be a custom type name.
		return $varType;
	}
}
