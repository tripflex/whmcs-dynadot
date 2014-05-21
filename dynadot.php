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

if ( !defined( "WHMCS" ) ) {
	die( "This file cannot be accessed directly" );
}

require_once( dirname( __FILE__ ) . "/inc/core.php" );

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
