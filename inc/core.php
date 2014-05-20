<?php

class WHMCS_Dynadot {

	const version = '2.0.0';
	const api_url = 'https://api.dynadot.com/api3.xml?key=';
	public $error;
	private $command;
	private $domain;

	public function __construct() {

	}

	public function check_for_update() {
		$url     = 'https://github.com/tripflex/whmcs-dynadot/raw/master/release';
		$release = file_get_contents( $url, "r" );
		if ( intval( $release ) > intval( $this->version ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function domainInfo( $xml ) {

	}

	public function getNS( $params ) {
		$this->setCommand( 'domain_info' );
		$response    = $this->api( $params );
		$nameservers = $response->xpath( '//NameServerSettings/NameServers' )[0];

		//	    Convert XML object to array hack
		$ns_array = json_decode( json_encode( $nameservers->ServerName ), 1 );

		foreach ( $ns_array as $ns_index => $ns_value ) {
			// Check to make sure isn't blank array
			if ( $ns_value ) {
				$ns_num        = $ns_index + 1;
				$ns            = 'ns' . $ns_num;
				$values[ $ns ] = $ns_value;
			}
		}

		return $values;
	}

	public function api( $params ) {
		$api_key = $params['APIKey'];
		$this->setDomain( $params );

		$query = self::api_url . $api_key . '&command=' . $this->getCommand() . '&domain=' . $this->getDomain();

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
			logModuleCall( 'dynadot', 'dynadot ' . $this->getCommand() . ' error', $response, $error );
		}

		logModuleCall( 'dynadot', 'dynadot ' . $this->getCommand(), $query, $result );

		return $response;
	}

	//	Getters and Setters

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
	public static function getError() {
		return self::$error;
	}

	/**
	 * @param mixed $error
	 */
	public static function setError( $error ) {
		self::$error = $error;
	}

	/**
	 * @return mixed
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * @param mixed $params
	 */
	public function setDomain( $params ) {
		$this->domain = $params['sld'] . '.' . $params['tld'];
	}
}
