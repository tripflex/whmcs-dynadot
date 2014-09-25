<?php

if ( ! defined( "WHMCS" ) ) die();

class WHMCS_Dynadot_Renew extends WHMCS_Dynadot {

	function __construct( $params ) {
		parent::__construct( $params );
	}

	public function renew() {

		$params = $this->getParams();
		$this->setCommand( 'renew' );
		$this->setArgument( 'duration', $params[ 'regperiod' ] );
		$this->debug( 'Renewing domain ' . $this->getDomain(), $this->getArguments() );
		$this->callAPI();

		return $this->getValues();
	}

}