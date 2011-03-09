<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2011 - Boxalino AG
 */


/**
 * Info gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_INFO_GatewayRequest extends CEM_GatewayRequest {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * Get request body content-type
	 *
	 * @return string request body content-type
	 */
	public function getContentType() {
		return NULL;
	}

	/**
	 * Called to write the request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return string request raw body
	 */
	public function write(&$state) {
		return NULL;
	}
}

?>