<?php
/**
 * BigBite Coding Standards.
 *
 * @package BigBiteCS\BigBite
 * @link    https://github.com/bigbite/phpcs-config
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace BigBiteCS\BigBite\Sniffs\Files;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\ObjectDeclarations;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Helpers\IsUnitTestTrait;
use WordPressCS\WordPress\Sniff;

/**
 * Ensures filenames are kebab-case, and are named appropriately.
 *
 * This is an extension of the WordPress.Files.FileName sniff.
 * In WPCS v3.0.0, the FileName sniff was marked as final, so
 * we can no longer extend it. Instead, we have copied the
 * relevant parts of the sniff here, and made the necessary
 * changes to account for abstract classes, traits and interfaces.
 *
 * Long term, it may be a good idea to check with the WPCS team,
 * to see if they would be interested in a backported PR to
 * the core sniff.
 */
final class FileNameSniff extends Sniff {

	use IsUnitTestTrait;

	/**
	 * Regex for the theme specific exceptions.
	 *
	 * N.B. This regex currently does not allow for mimetype sublevel only file names,
	 * such as `plain.php`.
	 *
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#custom-taxonomies
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#custom-post-types
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#embeds
	 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#attachment
	 * @link https://developer.wordpress.org/themes/template-files-section/partial-and-miscellaneous-template-files/#content-slug-php
	 * @link https://wphierarchy.com/
	 * @link https://en.wikipedia.org/wiki/Media_type#Naming
	 *
	 * @since 0.11.0
	 *
	 * @var string
	 */
	const THEME_EXCEPTIONS_REGEX = '`
		^                    # Anchor to the beginning of the string.
		(?:
							 # Template prefixes which can have exceptions.
			(?:archive|category|content|embed|page|single|tag|taxonomy)
			-[^\.]+          # These need to be followed by a dash and some chars.
		|
			(?:application|audio|example|image|message|model|multipart|text|video) #Top-level mime-types
			(?:_[^\.]+)?     # Optionally followed by an underscore and a sub-type.
		)\.(?:php|inc)$      # End in .php (or .inc for the test files) and anchor to the end of the string.
	`Dx';

	/**
	 * Whether the codebase being sniffed is a theme.
	 *
	 * If true, it will allow for certain typical theme specific exceptions to the filename rules.
	 *
	 * @since 0.11.0
	 *
	 * @var bool
	 */
	public $is_theme = false;

	/**
	 * Whether to apply strict file name rules.
	 *
	 * If true, it demands that classes/traits/interfaces are prefixed
	 * with the appropriate prefix ("type-"), and that the rest of the
	 * file name reflects the type name.
	 *
	 * @since 0.11.0
	 *
	 * @var bool
	 */
	public $strict_file_names = true;

	/**
	 * Historical exceptions in WP core to the class name rule.
	 *
	 * Note: these files were renamed to comply with the naming conventions in
	 * WP 6.1.0.
	 * This means we no longer need to make an exception for them in the
	 * `check_filename_has_class_prefix()` check, however, we do still need to
	 * make an exception in the `check_filename_is_hyphenated()` check.
	 *
	 * @since 0.11.0
	 * @since 3.0.0  Property has been renamed from `$class_exceptions` to `$hyphenation_exceptions`,
	 *
	 * @var array<string,bool>
	 */
	private $hyphenation_exceptions = array(
		'class.wp-dependencies.php' => true,
		'class.wp-scripts.php'      => true,
		'class.wp-styles.php'       => true,
		'functions.wp-scripts.php'  => true,
		'functions.wp-styles.php'   => true,
	);

	/**
	 * Unit test version of the historical exceptions in WP core.
	 *
	 * @since 0.11.0
	 * @since 3.0.0  Property has been renamed from `$unittest_class_exceptions` to `$unittest_hyphenation_exceptions`,
	 *
	 * @var array<string,bool>
	 */
	private $unittest_hyphenation_exceptions = array(
		'class.wp-dependencies.inc' => true,
		'class.wp-scripts.inc'      => true,
		'class.wp-styles.inc'       => true,
		'functions.wp-scripts.inc'  => true,
		'functions.wp-styles.inc'   => true,
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array<int,int>
	 */
	public function register() {
		if ( \defined( '\PHP_CODESNIFFER_IN_TESTS' ) ) {
			$this->hyphenation_exceptions += $this->unittest_hyphenation_exceptions;
		}

		return Collections::phpOpenTags();
	}

	/**
	 * Processes this test when one of its tokens is encountered.
	 *
	 * @param int $stack_ptr The position of the current token in the stack.
	 *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
	 */
	public function process_token( $stack_ptr ) {
		// Usage of `stripQuotes` is to ensure `stdin_path` passed by IDEs does not include quotes.
		$file = TextStrings::stripQuotes( $this->phpcsFile->getFileName() );
		if ( 'STDIN' === $file ) {
			return;
		}

		$class_ptr     = $this->phpcsFile->findNext( \T_CLASS, $stack_ptr );
		$trait_ptr     = $this->phpcsFile->findNext( \T_TRAIT, $stack_ptr );
		$interface_ptr = $this->phpcsFile->findNext( \T_INTERFACE, $stack_ptr );

		if ( false !== $class_ptr && $this->is_test_class( $this->phpcsFile, $class_ptr ) ) {
			/*
			 * This rule should not be applied to test classes (at all).
			 * @link https://github.com/WordPress/WordPress-Coding-Standards/issues/1995
			 */
			return;
		}

		if ( $this->is_disabled_by_comments() ) {
			return;
		}

		$file_name = basename( $file );

		// plain file, just check hyphenation.
		if ( ! $class_ptr && ! $trait_ptr && ! $interface_ptr ) {
			$this->check_filename_is_hyphenated( $file_name );
			return ( $this->phpcsFile->numTokens + 1 );
		}

		// not a plain file, but not strict, just check hyphenation.
		if ( true !== $this->strict_file_names ) {
			$this->check_filename_is_hyphenated( $file_name );
			return ( $this->phpcsFile->numTokens + 1 );
		}

		// check for "(abstract-)class-" prefix.
		if ( false !== $class_ptr ) {
			$this->check_filename_has_class_prefix( $class_ptr, $file_name );
			return ( $this->phpcsFile->numTokens + 1 );
		}

		// check for "trait-" prefix.
		if ( false !== $trait_ptr ) {
			$this->check_filename_has_trait_prefix( $trait_ptr, $file_name );
			return ( $this->phpcsFile->numTokens + 1 );
		}

		// check for "interface-" prefix.
		if ( false !== $interface_ptr ) {
			$this->check_filename_has_interface_prefix( $interface_ptr, $file_name );
			return ( $this->phpcsFile->numTokens + 1 );
		}

		$is_wpinc_path = false !== strpos( $file, \DIRECTORY_SEPARATOR . 'wp-includes' . \DIRECTORY_SEPARATOR );

		if ( $is_wpinc_path && false === $class_ptr ) {
			$this->check_filename_for_template_suffix( $stack_ptr, $file_name );
		}

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

		// Respect phpcs:disable comments as long as they are not accompanied by an enable.
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
	 * Generic check for lowercase hyphenated file names.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file_name The name of the current file.
	 *
	 * @throws RuntimeException If the file name does not contain an extension.
	 *
	 * @return void
	 */
	protected function check_filename_is_hyphenated( $file_name ) {
		$extension = strrchr( $file_name, '.' );

		if ( ! is_string( $extension ) ) {
			throw new RuntimeException( sprintf( 'File name without extension found: "%s"', $file_name ) );
		}

		$name     = substr( $file_name, 0, ( strlen( $file_name ) - strlen( $extension ) ) );
		$expected = $this->kebab( $name ) . $extension;

		if ( $file_name === $expected || isset( $this->hyphenation_exceptions[ $file_name ] ) ) {
			return;
		}

		if ( true === $this->is_theme && 1 === preg_match( self::THEME_EXCEPTIONS_REGEX, $file_name ) ) {
			return;
		}

		$this->phpcsFile->addError(
			'Filenames should be all lowercase with hyphens as word separators. Expected %s, but found %s.',
			0,
			'NotHyphenatedLowercase',
			array( $expected, $file_name )
		);
	}

	/**
	 * Check files containing a class for the "class-" prefix,
	 * and that the rest of the file name reflects the class name.
	 * Accounts for abstract classes.
	 *
	 * @param mixed  $class_ptr the token stack.
	 * @param string $file_name the name of the file.
	 *
	 * @return bool
	 */
	protected function check_filename_has_class_prefix( $class_ptr, $file_name ) {
		$extension   = strrchr( $file_name, '.' );
		$class_name  = ObjectDeclarations::getName( $this->phpcsFile, $class_ptr );
		$properties  = ObjectDeclarations::getClassProperties( $this->phpcsFile, $class_ptr );
		$expected    = 'class-' . $this->kebab( $class_name ) . $extension;
		$err_message = 'Class file names should be based on the class name with "class-" prepended. Expected %s, but found %s.';

		if ( $properties['is_abstract'] ) {
			$expected    = 'abstract-' . $expected;
			$err_message = 'Abstract class file names should be based on the class name with "abstract-class-" prepended. Expected %s, but found %s.';
		}

		if ( $file_name === $expected ) {
			return true;
		}

		$this->phpcsFile->addError( $err_message, 0, 'InvalidClassFileName', array( $expected, $file_name ) );

		return false;
	}

	/**
	 * Check files containing a trait for the "trait-" prefix,
	 * and that the rest of the file name reflects the trait name.
	 *
	 * @param mixed  $trait_ptr the token stack.
	 * @param string $file_name the name of the file.
	 *
	 * @return bool
	 */
	protected function check_filename_has_trait_prefix( $trait_ptr, $file_name ) {
		$extension   = strrchr( $file_name, '.' );
		$trait_name  = ObjectDeclarations::getName( $this->phpcsFile, $trait_ptr );
		$expected    = 'trait-' . $this->kebab( $trait_name ) . $extension;
		$err_message = 'Trait file names should be based on the trait name with "trait-" prepended. Expected %s, but found %s.';

		if ( $file_name === $expected ) {
			return true;
		}

		$this->phpcsFile->addError( $err_message, 0, 'InvalidTraitFileName', array( $expected, $file_name ) );

		return false;
	}

	/**
	 * Check files containing an interface for the "interface-" prefix,
	 * and that the rest of the file name reflects the interface name.
	 *
	 * @param mixed  $interface_ptr the token stack.
	 * @param string $file_name     the name of the file.
	 *
	 * @return bool
	 */
	protected function check_filename_has_interface_prefix( $interface_ptr, $file_name ) {
		$extension      = strrchr( $file_name, '.' );
		$interface_name = ObjectDeclarations::getName( $this->phpcsFile, $interface_ptr );
		$expected       = 'interface-' . $this->kebab( $interface_name ) . $extension;
		$err_message    = 'Interface file names should be based on the interface name with "interface-" prepended. Expected %s, but found %s.';

		if ( $file_name === $expected ) {
			return true;
		}

		$this->phpcsFile->addError( $err_message, 0, 'InvalidInterfaceFileName', array( $expected, $file_name ) );

		return false;
	}

	/**
	 * Check non-class files in "wp-includes" with a "@subpackage Template" tag for a "-template" suffix.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $stackPtr  Stack pointer to the first PHP open tag in the file.
	 * @param string $file_name The name of the current file.
	 *
	 * @return void
	 */
	protected function check_filename_for_template_suffix( $stackPtr, $file_name ) {
		$subpackage_tag = $this->phpcsFile->findNext( \T_DOC_COMMENT_TAG, $stackPtr, null, false, '@subpackage' );
		if ( false === $subpackage_tag ) {
			return;
		}

		$subpackage = $this->phpcsFile->findNext( \T_DOC_COMMENT_STRING, $subpackage_tag );
		if ( false === $subpackage ) {
			return;
		}

		$fileName_end = substr( $file_name, -13 );

		if ( ( 'Template' === trim( $this->tokens[ $subpackage ]['content'] )
			&& $this->tokens[ $subpackage_tag ]['line'] === $this->tokens[ $subpackage ]['line'] )
			&& ( ( ! \defined( '\PHP_CODESNIFFER_IN_TESTS' ) && '-template.php' !== $fileName_end )
			|| ( \defined( '\PHP_CODESNIFFER_IN_TESTS' ) && '-template.inc' !== $fileName_end ) )
		) {
			$this->phpcsFile->addError(
				'Files containing template tags should have "-template" appended to the end of the file name. Expected %s, but found %s.',
				0,
				'InvalidTemplateTagFileName',
				array(
					substr( $file_name, 0, -4 ) . '-template.php',
					$file_name,
				)
			);
		}
	}

	/**
	 * Convert a string to kebab-case
	 *
	 * @param string $filename the string to texturise.
	 *
	 * @return string
	 */
	protected function kebab( $filename = '' ) {
		$kebab = preg_replace( '`[[:punct:]]`', '-', $filename );
		$kebab = preg_replace( '/(?>(?!^[A-Z]))([a-z])([A-Z])/', '$1-$2', $filename );
		$kebab = strtolower( $kebab );
		$kebab = str_replace( '_', '-', $kebab );

		// allow wordpress to be one word.
		if ( false !== strpos( $filename, 'WordPress' ) ) {
			$kebab = str_replace( 'word-press', 'wordpress', $kebab );
		}

		return $kebab;
	}
}
