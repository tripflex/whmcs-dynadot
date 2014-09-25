<?php
/**
 * @title               WHMCS Dynadot Registar Module
 *
 * @author              Myles McNamara (get@smyl.es)
 * @url                 http://smyl.es
 * @github              https://github.com/tripflex/whmcs-dynadot
 * @copyright           Copyright (c) Myles McNamara 2014
 * @license             GPLv3+
 * @Date                5/20/14
 */

if ( !defined( "WHMCS" ) ) die( "This file cannot be accessed directly" );

function dynadot_getconfigarray() {
	$configarray = array(
		"APIKey" => array(
			"Type"        => "text",
			"Size"        => "20",
			"Description" => "Put your Dynadot API v3 Key here."
		)
	);

	return $configarray;
}

function dynadot_GetNameservers( $params ) {
	$dynadot = new WHMCS_Dynadot_NS( $params );

	return $dynadot->getNS();
}

function dynadot_SaveNameservers( $params ) {
	$dynadot = new WHMCS_Dynadot_NS( $params );

	return $dynadot->saveNS();
}

function dynadot_RegisterDomain( $params ) {
	$dynadot = new WHMCS_Dynadot_Register( $params );

	return $dynadot->register();
}

function dynadot_RenewDomain( $params ) {
	$dynadot = new WHMCS_Dynadot_Renew( $params );

	return $dynadot->renew();
}

function dynadot_getDNS( $params ) {
	$dynadot = new WHMCS_Dynadot_DNS( $params );
	return $dynadot->getDNS( $params );
}

function dynadot_SaveDNS( $params ) {
	$dynadot = new WHMCS_Dynadot_DNS( $params );
	return $dynadot->SaveDNS( $params );
}

function dynadot_autoload( $class ){

	$class_file = str_replace( 'WHMCS_Dynadot_', '', $class );
	$file_array = array_map( 'strtolower', explode( '_', $class_file ) );

	$dirs = 0;
	$file = dirname( __FILE__ ) . '/classes/';

	if ( $class === 'WHMCS_Dynadot' ) {
		include $file . 'core.php';
		return;
	}

	while ( $dirs ++ < count( $file_array ) ) {
		$file .= '/' . $file_array[ $dirs - 1 ];
	}

	$file .= '.php';

	if( ! file_exists( $file ) ) return;

	include $file;
}

spl_autoload_register( 'dynadot_autoload' );