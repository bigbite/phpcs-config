<?php

/**
 * BigBite Coding Standards.
 *
 * @package BigBite\phpcs-config
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

declare( strict_types = 1 );

namespace BigBiteCS\BigBite\Sniffs\Files;

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
		// strip quotes to ensure `stdin_path` passed by IDEs does not include quotes.
		$file = preg_replace( '`^([\'"])(.*)\1$`Ds', '$2', $this->phpcsFile->getFileName() );

		if ( 'STDIN' === $file || ! $file ) {
			return;
		}

		if ( $this->is_disabled_by_comments() ) {
			return;
		}

		$fileName = basename( $file );

		list( $ext, $file ) = explode( '.', strrev( $fileName ), 2 );

		$expected = $this->kebab( strrev( $file ) ) . '.' . strrev( $ext );

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

		if ( true !== $this->strict_class_file_names ) {
			return ( $this->phpcsFile->numTokens + 1 );
		}

		$this->check_maybe_class( $stackPtr, $fileName );
		$this->check_maybe_trait( $stackPtr, $fileName );
		$this->check_maybe_interface( $stackPtr, $fileName );

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
				|| isset( $this->tokens[ $i ]['sniffCodes']['BigBite'] )
				|| isset( $this->tokens[ $i ]['sniffCodes']['BigBite.Files'] )
				|| isset( $this->tokens[ $i ]['sniffCodes']['BigBite.Files.FileName'] )
			) {
				do {
					$i = $this->phpcsFile->findNext( \T_PHPCS_ENABLE, ( $i + 1 ) );
				} while ( false !== $i
					&& ! empty( $this->tokens[ $i ]['sniffCodes'] )
					&& ! isset( $this->tokens[ $i ]['sniffCodes']['BigBite'] )
					&& ! isset( $this->tokens[ $i ]['sniffCodes']['BigBite.Files'] )
					&& ! isset( $this->tokens[ $i ]['sniffCodes']['BigBite.Files.FileName'] ) );

				if ( false === $i ) {
					// The entire (rest of the) file is disabled.
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check files containing a class for the "class-" prefix,
	 * and that the rest of the file name reflects the class name.
	 * Accounts for abstract classes.
	 *
	 * @param mixed  $stackPtr the token stack
	 * @param string $fileName the name of the file
	 *
	 * @return void
	 */
	protected function check_maybe_class( $stackPtr, $fileName ) {
		$has_class = $this->phpcsFile->findNext( \T_CLASS, $stackPtr );

		if ( false === $has_class || false !== $this->is_test_class( $has_class ) ) {
			return;
		}

		$is_abstract = $this->phpcsFile->findPrevious( \T_ABSTRACT, $has_class );
		$class_name  = $this->phpcsFile->getDeclarationName( $has_class );

		if ( is_null( $class_name ) ) {
			$class_name = 'anonymous';
		}

		$expected    = 'class-' . $this->kebab( $class_name );
		$err_message = 'Class file names should be based on the class name with "class-" prepended. Expected %s, but found %s.';

		if ( $is_abstract ) {
			$expected    = 'abstract-' . $expected;
			$err_message = 'Abstract class file names should be based on the class name with "abstract-class-" prepended. Expected %s, but found %s.';
		}

		if ( substr( $fileName, 0, -4 ) === $expected ) {
			return;
		}

		$this->phpcsFile->addError( $err_message, 0, 'InvalidClassFileName', [ $expected . '.php', $fileName ] );
	}

	/**
	 * Check files containing a trait for the "trait-" prefix,
	 * and that the rest of the file name reflects the trait name.
	 *
	 * @param mixed  $stackPtr the token stack
	 * @param string $fileName the name of the file
	 *
	 * @return void
	 */
	protected function check_maybe_trait( $stackPtr, $fileName ) {
		$has_trait = $this->phpcsFile->findNext( \T_TRAIT, $stackPtr );

		if ( false === $has_trait || false !== $this->is_test_class( $has_trait ) ) {
			return;
		}

		$trait_name  = $this->phpcsFile->getDeclarationName( $has_trait );
		$expected    = 'trait-' . $this->kebab( $trait_name );
		$err_message = 'Trait file names should be based on the class name with "trait-" prepended. Expected %s, but found %s.';

		if ( substr( $fileName, 0, -4 ) === $expected ) {
			return;
		}

		$this->phpcsFile->addError( $err_message, 0, 'InvalidTraitFileName', [ $expected . '.php', $fileName ] );
	}

	/**
	 * Check files containing an interface for the "interface-" prefix,
	 * and that the rest of the file name reflects the interface name.
	 *
	 * @param mixed  $stackPtr the token stack
	 * @param string $fileName the name of the file
	 *
	 * @return void
	 */
	protected function check_maybe_interface( $stackPtr, $fileName ) {
		$has_interface = $this->phpcsFile->findNext( \T_INTERFACE, $stackPtr );

		if ( false === $has_interface || false !== $this->is_test_class( $has_interface ) ) {
			return;
		}

		$interface_name = $this->phpcsFile->getDeclarationName( $has_interface );
		$expected       = 'interface-' . $this->kebab( $interface_name );
		$err_message    = 'Interface file names should be based on the interface name with "interface-" prepended. Expected %s, but found %s.';

		if ( substr( $fileName, 0, -4 ) === $expected ) {
			return;
		}

		$this->phpcsFile->addError( $err_message, 0, 'InvalidInterfaceFileName', [ $expected . '.php', $fileName ] );
	}

	/**
	 * Convert a string to kebab-case
	 *
	 * @param string $string the string to texturise
	 *
	 * @return string
	 */
	protected function kebab( $string = '' ) {
		$kebab = preg_replace( '/(?>(?!^[A-Z]))([a-z])([A-Z])/', '$1-$2', $string );
		$kebab = strtolower( $kebab );
		$kebab = str_replace( '_', '-', $kebab );

		// allow wordpress to be one word
		if ( false !== strpos( $string, 'WordPress' ) ) {
			$kebab = str_replace( 'word-press', 'wordpress', $kebab );
		}

		return $kebab;
	}

}
