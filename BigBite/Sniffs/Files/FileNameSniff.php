<?php

/**
 * BigBite Coding Standards.
 *
 * @package bigbite\phpcs-config
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

declare( strict_types = 1 );

namespace BigBiteCS\BigBite\Sniffs\Files;

use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Sniffs\Files\FileNameSniff as WPFileNameSniff;

/**
 * Ensures filenames are kebab-case, and are named appropriately
 */
class FileNameSniff extends WPFileNameSniff {

	/**
	 * Processes this test when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_token( $stackPtr ) {
		// Usage of `stripQuotes` is to ensure `stdin_path` passed by IDEs does not include quotes.
		$file = TextStrings::stripQuotes( $this->phpcsFile->getFileName() );

		if ( 'STDIN' === $file ) {
			return;
		}

		if ( $this->is_disabled_by_comments() ) {
			return;
		}

		$fileName = basename( $file );
		$expected = strtolower( str_replace( '_', '-', $fileName ) );

		/*
		 * Generic check for lowercase hyphenated file names.
		 */
		if ( $fileName !== $expected && ( false === $this->is_theme || 1 !== preg_match( self::THEME_EXCEPTIONS_REGEX, $fileName ) ) ) {
			$this->phpcsFile->addError(
				'Filenames should be all lowercase with hyphens as word separators. Expected %s, but found %s.',
				0,
				'NotHyphenatedLowercase',
				[ $expected, $fileName ]
			);
		}

		unset( $expected );

		/*
		 * Check files containing a class for the "class-" prefix and that the rest of
		 * the file name reflects the class name. Accounts for abstract classes.
		 */
		if ( true === $this->strict_class_file_names ) {
			$has_class = $this->phpcsFile->findNext( \T_CLASS, $stackPtr );

			if ( false !== $has_class && false === $this->is_test_class( $this->phpcsFile, $has_class ) ) {
				$is_abstract = $this->phpcsFile->findPrevious( \T_ABSTRACT, $has_class );
				$class_name  = $this->phpcsFile->getDeclarationName( $has_class );
				$expected    = 'class-' . strtolower( str_replace( '_', '-', $class_name ) );
				$err_message = 'Class file names should be based on the class name with "class-" prepended. Expected %s, but found %s.';

				if ( $is_abstract ) {
					$expected    = 'abstract-' . $expected;
					$err_message = 'Abstract class file names should be based on the class name with "abstract-class-" prepended. Expected %s, but found %s.';
				}

				if ( substr( $fileName, 0, -4 ) !== $expected && ! isset( $this->class_exceptions[ $fileName ] ) ) {
					$this->phpcsFile->addError( $err_message, 0, 'InvalidClassFileName', [ $expected . '.php', $fileName ] );
				}

				unset( $expected );
			}
		}

		/*
		 * Check non-class files in "wp-includes" with a "@subpackage Template" tag for a "-template" suffix.
		 */
		$this->check_non_class_files( $file, $fileName, $stackPtr );

		// Only run this sniff once per file, no need to run it again.
		return ( $this->phpcsFile->numTokens + 1 );
	}

	/**
	 * Respect phpcs:disable comments as long as they are not accompanied by an enable (PHPCS 3.2+).
	 *
	 * @return bool
	 */
	protected function is_disabled_by_comments() {
		if ( ! \defined( '\T_PHPCS_DISABLE' ) || ! \defined( '\T_PHPCS_ENABLE' ) ) {
			return false;
		}

		$i = -1;
		while ( $i = $this->phpcsFile->findNext( \T_PHPCS_DISABLE, ( $i + 1 ) ) ) {
			if ( empty( $this->tokens[ $i ]['sniffCodes'] )
				|| isset( $this->tokens[ $i ]['sniffCodes']['WordPress'] )
				|| isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files'] )
				|| isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files.FileName'] )
			) {
				do {
					$i = $this->phpcsFile->findNext( \T_PHPCS_ENABLE, ( $i + 1 ) );
				} while ( false !== $i
					&& ! empty( $this->tokens[ $i ]['sniffCodes'] )
					&& ! isset( $this->tokens[ $i ]['sniffCodes']['WordPress'] )
					&& ! isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files'] )
					&& ! isset( $this->tokens[ $i ]['sniffCodes']['WordPress.Files.FileName'] ) );

				if ( false === $i ) {
					// The entire (rest of the) file is disabled.
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check non-class files in "wp-includes" with a "@subpackage Template" tag for a "-template" suffix.
	 * 
	 * @param mixed $file     the file to check
	 * @param mixed $fileName the name of the file to check
	 * @param mixed $stackPtr the token stack 
	 * 
	 * @return void
	 */
	protected function check_non_class_files( $file, $fileName, $stackPtr ) {
		if ( false === strpos( $file, \DIRECTORY_SEPARATOR . 'wp-includes' . \DIRECTORY_SEPARATOR ) ) {
			return;
		}

		$subpackage_tag = $this->phpcsFile->findNext( \T_DOC_COMMENT_TAG, $stackPtr, null, false, '@subpackage' );

		if ( false === $subpackage_tag ) {
			return;
		}

		$subpackage = $this->phpcsFile->findNext( \T_DOC_COMMENT_STRING, $subpackage_tag );

		if ( false === $subpackage ) {
			return;
		}

		$fileName_end = substr( $fileName, -13 );
		$has_class    = $this->phpcsFile->findNext( \T_CLASS, $stackPtr );

		if ( ( 'Template' === trim( $this->tokens[ $subpackage ]['content'] )
			&& $this->tokens[ $subpackage_tag ]['line'] === $this->tokens[ $subpackage ]['line'] )
			&& ( ( ! \defined( '\PHP_CODESNIFFER_IN_TESTS' ) && '-template.php' !== $fileName_end )
			|| ( \defined( '\PHP_CODESNIFFER_IN_TESTS' ) && '-template.inc' !== $fileName_end ) )
			&& false === $has_class
		) {
			$this->phpcsFile->addError(
				'Files containing template tags should have "-template" appended to the end of the file name. Expected %s, but found %s.',
				0,
				'InvalidTemplateTagFileName',
				[
					substr( $fileName, 0, -4 ) . '-template.php',
					$fileName,
				]
			);
		}
	}

}
