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
	protected $arguments = array();
	protected $account_ns;

	public function __construct( $params ) {
		$this->setParams( $params );
		$this->setDomain( $params['sld'] . '.' . $params['tld'] );
		$this->setApiKey( $params['APIKey'] );
	}

	public static function check_for_update() {
		$url     = 'https://github.com/tripflex/whmcs-dynadot/raw/master/release';
		$release = file_get_contents( $url, "r" );
		logModuleCall( 'dynadot', 'update check', self::version, intval($release));
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

	public function epochToDate( $epoch ) {
		$date = new DateTime( "@$epoch" );

		return $date->format( 'Y-m-d' );
	}

	public function xmlToArrayJSON( $xml ) {
		//	    Convert XML object to array hack
		$array = json_decode( json_encode( $xml ), 1 );

		return $array;
	}

	public function getNSlist() {
		$this->debug( 'Getting NS list from Dynadot...' );
		$this->setCommand( 'server_list' );
		$response    = $this->callAPI();
		$nameservers = $response->xpath( '//NameServerList/List' )[0];

		$ns_array = $this->xmlToArrayJSON( $nameservers );

		$this->debug( 'NS list retrieved:', $ns_array['Server'] );

		$this->setAccountNs( $ns_array['Server'] );
	}

	public function isNSinAccount( $ns_to_check ) {
		$this->debug( 'Checking if NS is already in account:', $ns_to_check );
		$ns_list  = $this->getAccountNs();
		$found_ns = false;

		foreach ( $ns_list as $ns_value ) {
			if ( in_array( $ns_to_check, $ns_value ) ) {
				$found_ns = true;
			}
		}

		return $found_ns;
	}

	public function addNSifNeeded( $ns ) {
		$skip_add_ns = $this->isNSinAccount( $ns );
		if ( !$skip_add_ns ) {
			$this->debug( 'NS does not exist in account, adding...', $ns );

			// Save current arguments in memory
			$save_args = $this->getArguments();

			// Remove arguments so we can insert argument for adding ns
			$this->setArguments( null );

			$this->setCommand( 'add_ns' );
			$this->setArgument( 'host', $ns );
			$this->callAPI();

			// Set arguments back to original
			$this->setArguments( $save_args );
		} else {
			$this->debug( 'Skip adding NS to account, already exists.', $skip_add_ns );
		}
	}

	public function saveNS() {
		$params = $this->getParams();

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
				$this->addNSifNeeded( $ns_whmcs_value );
				$this->setArgument( $ns_dd, $ns_whmcs_value );
			}
		}

		// Now attempt to set nameservers
		$this->setCommand( 'set_ns' );
		$this->callAPI();

		return $this->getValues();
	}

	public function getNS() {
		$this->setCommand( 'domain_info' );
		$response    = $this->callAPI();
		$nameservers = $response->xpath( '//NameServerSettings/NameServers' )[0];

		$ns_array = $this->xmlToArrayJSON( $nameservers->ServerName );

		foreach ( $ns_array as $ns_index => $ns_value ) {
			// Check to make sure isn't blank array
			if ( $ns_value ) {
				$ns_num = $ns_index + 1;
				$ns     = 'ns' . $ns_num;
				$this->setValue( $ns, $ns_value );
				$this->debug( 'Nameserver ' . $ns . ' found:', $ns_value );
			}
		}

		return $this->getValues();
	}

	public function register() {
		$params = $this->getParams();
		$this->setCommand( 'register' );
		$this->setArgument( 'duration', $params['regperiod'] );

		$this->callAPI();

		return $this->getValues();
	}

	public function renew() {
		$params = $this->getParams();
		$this->setCommand( 'renew' );
		$this->setArgument( 'duration', $params['regperiod'] );
		$this->debug( 'Renewing domain ' . $this->getDomain(), $this->getArguments() );
		$this->callAPI();

		return $this->getValues();
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

	/**
	 * @return array
	 */
	public function getAccountNs() {
		if ( !$this->account_ns ) {
			$this->getNSlist();
		}

		return $this->account_ns;
	}

	/**
	 * @param array $account_ns
	 */
	public function setAccountNs( $account_ns ) {
		$this->account_ns = $account_ns;
	}
}
