<?php

/** @addtogroup cem
 *
 * @{
 */

/**
 * @internal
 *
 * Boxalino CEM client library in PHP.
 *
 * (C) 2009-2012 - Boxalino AG
 */


/**
 * Abstract gateway request
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_GatewayRequest {
	/**
	 * Http referer
	 */
	protected $referer = NULL;
	

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get http referer
	 *
	 * @return http referer (or NULL if none)
	 */
	public function getReferer() {
		return $this->referer;
	}

	/**
	 * Set http referer
	 *
	 * @param $referer http referer (or NULL if none)
	 */
	public function setReferer($referer) {
		$this->referer = $referer;
	}

	/**
	 * Get request body character set
	 *
	 * @return request body character set
	 */
	public function getContentCharset() {
		return 'UTF-8';
	}

	/**
	 * Get request body content-type
	 *
	 * @return request body content-type
	 */
	public function getContentType() {
		return 'application/octet-stream';
	}

	/**
	 * Called to write the request
	 *
	 * @param &$state client state reference
	 * @return request raw body
	 */
	public abstract function write(&$state);
}

/**
 * @}
 */

?>