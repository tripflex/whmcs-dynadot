<?php
/**
 * @title               WHMCS Dynadot Registrar Module
 *
 * @author              Myles McNamara (get@smyl.es)
 * @copyright           Copyright (c) Myles McNamara 2014
 * @Date                :               5/20/14
 * @Last                Modified by:   Myles McNamara
 * @Last                Modified time: 20 13 08
 */

if ( !defined( "WHMCS" ) ) {
	die( "This file cannot be accessed directly" );
}

require_once( dirname( __FILE__ ) . "/inc/core.php" );

function dynadot_getconfigarray() {
	$configarray = array( "APIKey" => array( "Type"        => "text",
	                                         "Size"        => "20",
	                                         "Description" => "Put your Dynadot API v3 Key here."
	)
	);

	return $configarray;
}

function dynadot_GetNameservers( $params ) {
	$dynadot = new WHMCS_Dynadot( $params );

	return $dynadot->getNS();
}

function dynadot_SaveNameservers( $params ) {
	$dynadot = new WHMCS_Dynadot( $params );

	return $dynadot->saveNS();
}

function dynadot_RegisterDomain( $params ) {
	$dynadot = new WHMCS_Dynadot( $params );

	return $dynadot->register();
}

function dynadot_RenewDomain( $params ) {
	$dynadot = new WHMCS_Dynadot( $params );

	return $dynadot->renew();
}

?>
