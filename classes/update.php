<?php

if ( ! defined( "WHMCS" ) ) die();

if ( ! class_exists( 'WHMCS_Dynadot' ) ) require_once( dirname( __FILE__ ) . '/core.php' );

class WHMCS_Dynadot_Update extends WHMCS_Dynadot {

	public static function check_for_update() {

		$url     = 'https://github.com/tripflex/whmcs-dynadot/raw/master/release';
		$release = file_get_contents( $url, "r" );
		if ( intval( $release ) > intval( self::version ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}