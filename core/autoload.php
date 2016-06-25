<?php

// Register Autoload Classes

define( 'BASE_DIR', dirname( __DIR__ ) );

// echo BASE_DIR;

function loaderFunc( $class_name )
{
	$parts = explode( '\\', $class_name );
	
	// class filename ==> file.php | my_file.php
	$file_basename = lcfirst( array_pop( $parts ) ) . '.php';

	// convert Camelcase to underscores
	$file_basename = preg_replace_callback( '/[A-Z]/',
		function( $letter ){ 
			return '_'.strtolower( $letter[0] );
		},
		$file_basename );

	// convert capital to small letters
	foreach( $parts as &$part )
		$part = strtolower( $part );

	$file_path = BASE_DIR . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $parts ) .
			DIRECTORY_SEPARATOR . $file_basename;

	// require file if existed
	if( file_exists( $file_path ) ) require_once( $file_path );

	// print_r( $file );
}

spl_autoload_register( 'loaderFunc' );
