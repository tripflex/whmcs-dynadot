<?php

if ( ! defined( "WHMCS" ) ) die();

class WHMCS_Dynadot_Register extends WHMCS_Dynadot {

	function __construct( $params ) {

		parent::__construct( $params );
	}

	public function register() {

		$params = $this->getParams();
		$this->setCommand( 'register' );
		$this->setArgument( 'duration', $params[ 'regperiod' ] );

		$this->callAPI();

		return $this->getValues();
	}

}