<?php
/**
 * BigBite Coding Standard.
 *
 * Bootstrap file for running the tests. Adapted from WPCS
 *
 * - Load the PHPCS PHPUnit bootstrap file providing cross-version PHPUnit support.
 *   {@link https://github.com/squizlabs/PHP_CodeSniffer/pull/1384}
 * - Load the Composer autoload file.
 * - Automatically limit the testing to the BigBite tests.
 *
 * @package BigBiteCS\BigBite
 */

if ( ! defined( 'PHP_CODESNIFFER_IN_TESTS' ) ) {
	define( 'PHP_CODESNIFFER_IN_TESTS', true );
}

$ds = DIRECTORY_SEPARATOR;

/*
 * Load the necessary PHPCS files.
 */
// Get the PHPCS dir from an environment variable.
$phpcs_dir           = getenv( 'PHPCS_DIR' );
$composer_phpcs_path = dirname( __DIR__ ) . $ds . 'vendor' . $ds . 'squizlabs' . $ds . 'php_codesniffer';

if ( false === $phpcs_dir && is_dir( $composer_phpcs_path ) ) {
	// PHPCS installed via Composer.
	$phpcs_dir = $composer_phpcs_path;
} elseif ( false !== $phpcs_dir ) {
	/*
	 * PHPCS in a custom directory.
	 * For this to work, the `PHPCS_DIR` needs to be set in a custom `phpunit.xml` file.
	 */
	$phpcs_dir = realpath( $phpcs_dir );
}

// Try and load the PHPCS autoloader.
if ( false !== $phpcs_dir
	&& file_exists( $phpcs_dir . $ds . 'autoload.php' )
	&& file_exists( $phpcs_dir . $ds . 'tests' . $ds . 'bootstrap.php' )
) {
	require_once $phpcs_dir . $ds . 'autoload.php';
	require_once $phpcs_dir . $ds . 'tests' . $ds . 'bootstrap.php'; // PHPUnit 6.x+ support.
} else {
	echo 'Uh oh... can\'t find PHPCS.

If you use Composer, please run `composer install`.
Otherwise, make sure you set a `PHPCS_DIR` environment variable in your phpunit.xml file
pointing to the PHPCS directory and that PHPCSUtils is included in the `installed_paths`
for that PHPCS install.
';

	die( 1 );
}

$bigbite_standards = array(
	'BigBite' => true,
);

$all_standards   = PHP_CodeSniffer\Util\Standards::getInstalledStandards();
$all_standards[] = 'Generic';

$ignored_standards = array();
foreach ( $all_standards as $standard ) {
	if ( isset( $bigbite_standards[ $standard ] ) === true ) {
		continue;
	}

	$ignored_standards[] = $standard;
}

$standards_to_ignore_string = implode( ',', $ignored_standards );

/*
 * Set the PHPCS_IGNORE_TEST environment variable to ignore tests from other standards.
 */
// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv( "PHPCS_IGNORE_TESTS={$standards_to_ignore_string}" );

// Clean up.
unset( $ds, $phpcs_dir, $composer_phpcs_path, $all_standards, $ignored_standards, $standard, $standards_to_ignore_string );
