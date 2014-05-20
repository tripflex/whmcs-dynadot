<?php

function dynadot_getconfigarray( )
{
    $configarray = array( "APIKey" => array( "Type" => "text", "Size" => "20", "Description" => "Put your Dynadot API v3 Key here." ) );
    return $configarray;
}

function dynadot_getnameservers( $params )
{
    return $values;
}

function dynadot_savenameservers( $params )
{
    $query = "https://api.dynadot.com/api3.xml?key={$params['APIKey']}&command=set_ns&domain={$params['sld']}.{$params['tld']}";
    if ( $params['ns1'] )
    {
        $query .= "&ns0={$params['ns1']}";
    }
    if ( $params['ns2'] )
    {
        $query .= "&ns1={$params['ns2']}";
    }
    if ( $params['ns3'] )
    {
        $query .= "&ns2={$params['ns3']}";
    }
    if ( $params['ns4'] )
    {
        $query .= "&ns3={$params['ns4']}";
    }
    $ch = curl_init( );
    curl_setopt( $ch, CURLOPT_URL, $query );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    $result = curl_exec( $ch );
    curl_close( $ch );

    $response = simplexml_load_string($result);

    logModuleCall('dynadot', 'dynadot save ns', $query, $result);

    $responsexml = $response->SetNsHeader[0];

    if($responsexml->Status == 'error'){
    	$error = $responsexml->Error;
    	logModuleCall('dynadot', 'dynadot save ns error', $responsexml, $error);
        $values['error'] = $error;
    }

    return $values;
}

function dynadot_registerdomain( $params )
{
    $query = "https://api.dynadot.com/api3.xml?key={$params['APIKey']}&command=register&domain={$params['sld']}.{$params['tld']}&duration={$params['regperiod']}";
    $ch = curl_init( );
    curl_setopt( $ch, CURLOPT_URL, $query );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    $result = curl_exec( $ch );
    curl_close( $ch );
    $result = explode( ",", $result );
    if ( $result[1] != "success" )
    {
        $values['error'] = $result[1]." - ".$result[2];
    }
    return $values;
}

function dynadot_renewdomain( $params )
{
    $query = "https://api.dynadot.com/api3.xml?key={$params['APIKey']}&command=renew&domain={$params['sld']}.{$params['tld']}&duration={$params['regperiod']}";
    $ch = curl_init( );
    curl_setopt( $ch, CURLOPT_URL, $query );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    $result = curl_exec( $ch );
    curl_close( $ch );
    $result = explode( ",", $result );
    if ( $result[1] != "success" )
    {
        $values['error'] = $result[1]." - ".$result[2];
    }
    return $values;
}

?>
