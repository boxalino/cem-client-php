<?php

/**
 * @internal
 *
 * Boxalino CEM client library in PHP.
 *
 * (C) 2009-2011 - Boxalino AG
 */


/**
 * Info gateway request
 *
 * @author nitro@boxalino.com
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
	 * @return request body content-type
	 */
	public function getContentType() {
		return NULL;
	}

	/**
	 * Called to write the request
	 *
	 * @param &$state client state reference
	 * @return request raw body
	 */
	public function write(&$state) {
		return NULL;
	}
}

?>