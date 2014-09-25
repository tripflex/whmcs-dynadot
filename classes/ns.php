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

class WHMCS_Dynadot_NS extends WHMCS_Dynadot {

	function __construct( $params ) {

		parent::__construct( $params );

	}

	public function getNSlist() {

		$this->debug( 'Getting NS list from Dynadot...' );
		$this->setCommand( 'server_list' );
		$response    = $this->callAPI();

		if ( ! is_object( $response ) ) return FALSE;

		$nameservers = $response->xpath( '//NameServerList/List' )[ 0 ];

		$ns_array = $this->xmlToArrayJSON( $nameservers );

		$this->debug( 'NS list retrieved:', $ns_array[ 'Server' ] );

		$this->setAccountNs( $ns_array[ 'Server' ] );
	}

	public function isNSinAccount( $ns_to_check ) {

		$this->debug( 'Checking if NS is already in account:', $ns_to_check );
		$ns_list  = $this->getAccountNs();
		$found_ns = FALSE;

		foreach ( $ns_list as $ns_value ) {
			if ( in_array( $ns_to_check, $ns_value ) ) {
				$found_ns = TRUE;
			}
		}

		return $found_ns;
	}

	public function addNSifNeeded( $ns ) {

		$skip_add_ns = $this->isNSinAccount( $ns );
		if ( ! $skip_add_ns ) {
			$this->debug( 'NS does not exist in account, adding...', $ns );

			// Save current arguments in memory
			$save_args = $this->getArguments();

			// Remove arguments so we can insert argument for adding ns
			$this->setArguments( NULL );

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

		if ( ! is_object( $response ) ) return false;

		$nameservers = $response->xpath( '//NameServerSettings/NameServers' )[ 0 ];

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

	/**
	 * @return array
	 */
	public function getAccountNs() {

		if ( ! $this->account_ns ) {
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