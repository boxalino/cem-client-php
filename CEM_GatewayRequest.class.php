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
 * (C) 2009-2011 - Boxalino AG
 */


/**
 * Abstract gateway request
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_GatewayRequest {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get request body content-type
	 *
	 * @return request body content-type
	 */
	public abstract function getContentType();

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