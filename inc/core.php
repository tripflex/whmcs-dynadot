<?php

class WHMCS_Dynadot {

	const version = '2.0.0'

	public function __construct() {

	}

	public function check_for_update(){
        $url = 'https://github.com/tripflex/whmcs-dynadot/raw/master/release';
        $release = file_get_contents($url, "r");
        if (intval($release) > intval($this->version)){
            return true;
        } else {
            return false;
        }
    }

    public function api(){

    }
}
