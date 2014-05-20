<?php

class WHMCS_Dynadot {

	const version = '2.0.0';
	const api_url = 'https://api.dynadot.com/api3.xml?key=';
	public $enable_debug = false;
	protected $error;
	protected $command;
	protected $domain;
	protected $values = array();
	protected $params;
	protected $api_key;
	protected $arguments;

	public function __construct( $params ) {
		$this->setParams( $params );
		$this->setDomain( $params['sld'] . '.' . $params['tld'] );
		$this->setApiKey( $params['APIKey'] );
		// Debug Logging
		$this->doDebug();
	}

	public function doDebug() {
		$this->debug( debug_backtrace() );
	}

	public function check_for_update() {
		// Debug Logging
		$this->doDebug();
		$url     = 'https://github.com/tripflex/whmcs-dynadot/raw/master/release';
		$release = file_get_contents( $url, "r" );
		if ( intval( $release ) > intval( self::version ) ) {
			return true;
		} else {
			return false;
		}
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

	public function domainInfo( $xml ) {

	}

	public function epochToDate( $epoch ) {
		$date = new DateTime( "@$epoch" );

		return $date->format( 'Y-m-d' );
	}

	public function xmlToArray( $xml ) {
		// Debug Logging
		$this->doDebug();
		//	    Convert XML object to array hack
		$array = json_decode( json_encode( $xml ), 1 );

		return $array;
	}

	public function saveNS() {
		// Debug Logging
		$this->doDebug();

		$params = $this->getParams();

		$this->setCommand( 'set_ns' );

		// Relationship values from Dynadot to WHMCS
		$dynadot_to_whmcs = [
			'ns0' => 'ns1',
			'ns1' => 'ns2',
			'ns2' => 'ns3',
			'ns3' => 'ns4'
		];

		foreach ( $dynadot_to_whmcs as $ns_dd => $ns_whmcs ) {
			$ns_whmcs_value = $params[ $ns_whmcs ];
			if ( $ns_whmcs_value ) {
				$this->setArgument( $ns_dd, $ns_whmcs_value );
			}
		}

		$this->callAPI();

		return $this->getValues();
	}

	public function getNS() {
		// Debug Logging
		$this->doDebug();
		$this->setCommand( 'domain_info' );
		$response    = $this->callAPI();
		$nameservers = $response->xpath( '//NameServerSettings/NameServers' )[0];

		$ns_array = $this->xmlToArray( $nameservers->ServerName );

		foreach ( $ns_array as $ns_index => $ns_value ) {
			// Check to make sure isn't blank array
			if ( $ns_value ) {
				$ns_num = $ns_index + 1;
				$ns     = 'ns' . $ns_num;
				$this->setValue( $ns, $ns_value );
			}
		}

		return $this->getValues();
	}

	public function register() {
		// Debug Logging
		$this->doDebug();
		$params = $this->getParams();
		$this->setCommand( 'register' );
		$this->setArgument( 'duration', $params['regperiod'] );

		$this->callAPI();

		return $this->getValues();
	}

	public function renew() {
		// Debug Logging
		$this->doDebug();
		$params = $this->getParams();
		$this->setCommand( 'renew' );
		$this->setArgument( 'duration', $params['regperiod'] );

		$this->callAPI();

		return $this->getValues();
	}

	public function callAPI() {
		// Debug Logging
		$this->doDebug();

		$query = self::api_url . $this->getApiKey() . '&command=' . $this->getCommand() . '&domain=' . $this->getDomain();
		// If there are arguments, add to the query string
		if ( $this->getArguments() ) {
			$query .= '&' . $this->getArguments();
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $query );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec( $ch );
		curl_close( $ch );

		$response = simplexml_load_string( $result );

		// Check for errors
		$find_status = $response->xpath( '//Status' );
		$status      = (string) $find_status[0];

		if ( $status == 'error' ) {
			$find_error = $response->xpath( '//Error' );
			$error      = (string) $find_error[0];
			$this->setError( $error );
			$this->log( $response, $error, 'error' );
		}
		$this->log( $query, $result );

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
	public function setCommand( $command ) {
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
		$encoded_arguments = http_build_query( $this->arguments );

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
