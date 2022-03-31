<?php


if ( ! function_exists( 'pascal' ) ) {
	/**
	 * Convert a string to PascalCase
	 *
	 * @param  string  $string  the string to texturise
	 *
	 * @return string
	 */
	function pascal( $string = '' ) {
		$string = preg_replace( '/[\'"]/', '', $string );
		$string = preg_replace( '/[^a-zA-Z0-9]+/', ' ', $string );
		return preg_replace( '/\s+/', '', ucwords( $string ) );	}
}

if ( ! function_exists( 'camel' ) ) {
	/**
	 * Convert a string to camelCase
	 *
	 * @param  string  $string  the string to texturise
	 *
	 * @return string
	 */
	function camel( $string = '' ) {
		return lcfirst( pascal( $string ) );
	}
}

if ( ! function_exists( 'snake' ) ) {
	/**
	 * Convert a string to snake_case
	 *
	 * @param  string  $string  the string to texturise
	 *
	 * @return string
	 */
	function snake( $string = '' ) {
		return strtolower( preg_replace( '/(?>(?!^[A-Z]))([A-Z])/', '_$1', pascal( $string ) ) );
	}
}

if ( ! function_exists( 'kebab' ) ) {
	/**
	 * Convert a string to kebab-case
	 *
	 * @param  string  $string  the string to texturise
	 *
	 * @return string
	 */
	function kebab( $string = '' ) {
		return str_replace( '_', '-', snake( $string ) );
	}
}
