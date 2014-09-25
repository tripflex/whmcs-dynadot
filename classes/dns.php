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

class WHMCS_Dynadot_DNS extends WHMCS_Dynadot {

	function __construct( $params ) {

		parent::__construct( $params );

	}

	/**
	 * Set Forwarding Command
	 *
	 *
	 * @since @@version
	 *
	 * @param        $domain      The domain name you want to set; 100 domains can be set per request, make sure that they are seperated by commas.
	 * @param        $forward_url The url you want your domain forward to
	 * @param string $is_temp     Forward status of your domain you want, default value is 'temporary', if you want to forward permanently, use this parameter with 'no'
	 */
	function setForward( $forward_url, $is_temp = 'temporary' ) {

		$this->setArgument( 'forward_url', $forward_url );
		$this->setArgument( 'is_temp', $is_temp );
		$this->setCommand( 'set_forwarding' );

	}

	function setStealthForward( $stealth_url ){

		$this->setArgument( 'stealth_url', $stealth_url );
		$this->setCommand( 'set_stealth' );

	}

	function SaveDNS( $params ) {

		try {

			$existing_records = $this->GetDNS();
			// Filters out priority and uncessesary values in array so we can compare to existing records
			$form_post_records = array_map( array( $this, 'filterDNSarray' ), $params[ 'dnsrecords' ] );
			// Compare $existing_records to $form_post_records and return only modified or new records
			$records_to_process = array_filter( array_map( 'unserialize', array_diff( array_map( 'serialize', $form_post_records ), array_map( 'serialize', $existing_records ) ) ) );

			# Loop through the submitted records
			foreach ( $records_to_process AS $key => $values ) {
				// Clear command field
				$this->setCommand();
				$hostname = $values[ "hostname" ];
				$type     = $values[ "type" ];
				$address  = $values[ "address" ];
				$priority  = ( ! empty( $values[ "priority" ] ) ? $values['priority'] : 0 );

				if( empty( $hostname ) || empty( $type) || empty( $address ) ) continue;

				// Must use FQDN that includes domain
				if ( strpos( $hostname, $this->getDomain() ) === FALSE ) throw new Exception( 'You must use the full domain/subomdain (include your domain)' );

				if ( $type == 'URL' ) $this->setForward( $address );
				if ( $type == 'FRAME' ) $this->setStealthForward( $address );
				if ( $type == 'MX' ) $this->setMXRecord( $hostname, $address, $priority );

				$this->setDNSRecord( $type, $hostname, $address );
			}

		} catch (Exception $e){

			$this->setError( $e->getMessage() );

		}

		$values = $this->getValues();

		if( empty( $values['error'] ) ) $values['success'] = 'Sucessfully updated.';
		return $this->getValues();
	}

	function setMXRecord( $hostname, $address, $distance ){

		$this->setArgument( 'mx_host0', $address );

	}

	function setDNSRecord( $type, $hostname, $address ){

		if( ! $this->getCommand() ) {

			$this->setArgument( 'main_record_type', $type );
			$this->setArgument( 'main_record', $address );

			$this->setCommand( 'set_dns' );
		}

		$this->callAPI();

	}

	function GetDNS( $params = array() ) {

		$records = array();

		$this->setCommand( 'domain_info' );
		$response = $this->callAPI();
		if ( ! $response ) return false;

		$data = $this->xmlToArrayJSON( $response->xpath( '//NameServerSettings' )[ 0 ] );

		$dns_types = array( 'Dynadot Stealth Forwarding', 'Dynadot Forwarding', 'Dynadot DNS' );

		if ( in_array( $data[ 'Type' ], $dns_types ) ) {

			switch ( $data[ 'Type' ] ) {

				case 'Dynadot Stealth Forwarding':
					$records[ ] = array( 'hostname' => $this->getDomain(), 'type' => 'FRAME', 'address' => $data[ 'ForwardTo' ] );
					break;

				case 'Dynadot Forwarding':
					$records[ ] = array( 'hostname' => $this->getDomain(), 'type' => 'URL', 'address' => $data[ 'ForwardTo' ] );
					break;

				case 'Dynadot DNS':
					$records = $this->convertDNSarray( $data );
					break;
			}

		}

		return $records;
	}

	function convertDNSarray( $dynadotDNS ) {

		if ( $dynadotDNS[ 'MainDomain' ][ 'RecordType' ] == 'Forward' ) $dynadotDNS[ 'MainDomain' ][ 'RecordType' ] = 'URL';
		$records[ ] = array( 'hostname' => $this->getDomain(), 'type' => $dynadotDNS[ 'MainDomain' ][ 'RecordType' ], 'address' => $dynadotDNS[ 'MainDomain' ][ 'Record' ] );

		if ( ! empty( $dynadotDNS[ 'SubDomains' ][ 'SubDomain' ] ) ) {

			foreach ( $dynadotDNS[ 'SubDomains' ][ 'SubDomain' ] as $dnsRecord ) {

				if ( $dnsRecord[ 'RecordType' ] == 'Forward' ) $dnsRecord[ 'RecordType' ] = 'URL';
				$records[ ] = array( 'hostname' => $dnsRecord[ 'Subhost' ] . '.' . $this->getDomain(), 'type' => $dnsRecord[ 'RecordType' ], 'address' => $dnsRecord[ 'Record' ] );

			}

			foreach ( $dynadotDNS[ 'MxRecords' ][ 'MxRecord' ] as $mxRecord ) {

				$records[ ] = array( 'type' => 'MX', 'address' => $mxRecord[ 'Host' ], 'priority' => $mxRecord[ 'Distance' ] );

			}

		}

		return $records;

	}

	function filterDNSarray( & $dns ) {

		if ( empty( $dns[ 'address' ] ) ) return array();

		// Remove blank array items
		$dns = array_filter( $dns );

		if ( $dns[ 'priority' ] === 'N/A' ) unset( $dns[ 'priority' ] );
		if ( $dns[ 'type' ] === 'MX' && empty( $dns[ 'priority' ] ) ) $dns[ 'priority' ] = "0";

		return $dns;

	}
}