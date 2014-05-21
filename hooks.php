<?php
/**
 * @title               WHMCS Dynadot Registar Module Hooks
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

function dynadot_hook_check_update() {
	$notice      = '';
	$need_update = WHMCS_Dynadot::check_for_update();
	if ( $need_update ) {
		$notice = '<div class="infobox"><strong><span class="title">Dynadot Update Available!</span></strong><br>You can download the update for the Dynadot Registrar Module from <a href="https://github.com/tripflex/whmcs-dynadot">GitHub</a></div>';
	}

	return $notice;
}

add_hook( "AdminHomepage", 1, "dynadot_hook_check_update" );
