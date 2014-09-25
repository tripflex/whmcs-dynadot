<?php

/**
 * @title               WHMCS Dynadot Registar Class
 *
 * @author              Myles McNamara (get@smyl.es)
 * @url                 http://smyl.es
 * @github              https://github.com/tripflex/whmcs-dynadot
 * @copyright           Copyright (c) Myles McNamara 2014
 * @license             GPLv3+
 * @Date                5/20/14
 */

if ( ! defined( "WHMCS" ) ) die();

class WHMCS_Dynadot {

	const version = '2.1.0';
	const api_url = 'https://api.dynadot.com/api3.xml?key=';
	public $enable_debug = true;
	protected $error;
	protected $command;
	protected $domain;
	protected $values = array();
	protected $params;
	protected $api_key;
	protected $arguments = array();
	protected $account_ns;

	public function __construct( $params ) {
		$this->setParams( $params );
		$this->setDomain( $params['sld'] . '.' . $params['tld'] );
		$this->setApiKey( $params['APIKey'] );
	}

	public function debug( $request, $response = null, $extra = null ) {
		if ( $this->enable_debug ) {
			$this->log( $request, $response, $extra );
		}
	}

	public function log( $request, $response = null, $extra = null ) {
		$command = $this->getCommand();
		if ( $extra ) {
			$command .= ' ' . $extra;
		}
		logModuleCall( 'dynadot', $command, $request, $response, '', array( $this->getApiKey() ) );
	}

	public function epochToDate( $epoch ) {
		$date = new DateTime( "@$epoch" );

		return $date->format( 'Y-m-d' );
	}

	public function xmlToArrayJSON( $xml ) {
		//	    Convert XML object to array hack
		$array = json_decode( json_encode( $xml ), 1 );

		return $array;
	}

	public function callAPI() {
		$query = self::api_url . $this->getApiKey() . '&command=' . $this->getCommand() . '&domain=' . $this->getDomain();

		// If there are arguments, add to the query string
		if ( $this->getArguments() ) {
			$query .= '&' . $this->buildArguments();
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $query );
		curl_setopt( $ch, CURLOPT_HEADER, 'Content-Type:application/xml' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec( $ch );
		curl_close( $ch );

		$this->log( $query, $result );

		$response = simplexml_load_string( $result );

		if( ! $response ) return false;

		$this->debug( 'API Response (asXML)', $response->asXML() );

		// Check for errors
		$find_status = $response->xpath( '//Status' );
		$status      = (string) $find_status[0];

		$this->debug( 'API Status', $status );

		if ( $status == 'error' ) {
			$find_error = $response->xpath( '//Error' );
			$error      = (string) $find_error[0];
			$this->setError( $error );
			$this->log( $response, $error, 'error' );
		}

		return $response;
	}

	//	Getters and Setters

	/**
	 * @return mixed
	 */
	public function getValues() {
		return $this->values;
	}

	/**
	 * @param mixed $values
	 */
	public function setValues( $values ) {
		$this->values = $values;
	}

	public function setValue( $key, $value ) {
		$currentValues         = $this->getValues();
		$currentValues[ $key ] = $value;
		$this->setValues( $currentValues );
	}

	public function getValue( $key ) {
		$currentValues = $this->getValues();

		return $currentValues[ $key ];
	}

	/**
	 * @return mixed
	 */
	public function getCommand() {
		return $this->command;
	}

	/**
	 * @param mixed $command
	 */
	public function setCommand( $command = null ) {
		$this->command = $command;
	}

	/**
	 * @return mixed
	 */
	public function getError() {
		return $this->$error;
	}

	/**
	 * @param mixed $error
	 */
	public function setError( $error ) {
		$this->$error = $error;
		$this->setValue( 'error', $error );
	}

	/**
	 * @return mixed
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * @param mixed $domain
	 */
	public function setDomain( $domain ) {
		$this->domain = $domain;
	}

	/**
	 * @return mixed
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @param mixed $params
	 */
	public function setParams( $params ) {
		$this->params = $params;
	}

	/**
	 * @return mixed
	 */
	public function getApiKey() {
		return $this->api_key;
	}

	/**
	 * @param mixed $api_key
	 */
	public function setApiKey( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * @return mixed
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @return mixed
	 */
	public function buildArguments() {
		$this->debug( 'Building Arguments Array', $this->getArguments() );
		$encoded_arguments = http_build_query( $this->getArguments() );
		$this->debug( 'Building Arguments Built', $encoded_arguments );

		return $encoded_arguments;
	}

	/**
	 * @param mixed $arguments
	 */
	public function setArguments( $arguments ) {
		$this->arguments = $arguments;
	}

	public function setArgument( $argument, $value ) {
		$arguments              = $this->getArguments();
		$arguments[ $argument ] = $value;
		$this->setArguments( $arguments );
	}

	public function getArgument( $argument ) {
		$arguments = $this->getArguments();

		return $arguments[ $argument ];
	}

}
