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
 * Frontend API page request
 *
 * @author nitro@boxalino.com
 */
class CEM_API_PageRequest extends CEM_GatewayRequest {
	/**
	 * Page parameters
	 */
	protected $parameters;


	/**
	 * Constructor
	 *
	 * @param $uri page uri
	 * @param $parameters page parameters
	 */
	public function __construct($uri, $parameters) {
		parent::__construct();
		$this->parameters = $parameters;
		$this->parameters['uri'] = $uri;
		foreach (CEM_Analytics::getParameters() as $k => $v) {
			$this->parameters[$k] = $v;
		}
	}


	/**
	 * Get request body content-type
	 *
	 * @return request body content-type
	 */
	public function getContentType() {
		return "multipart/form-data";
	}

	/**
	 * Called to write the request
	 *
	 * @param $state client state reference
	 * @return request raw body (POST fields)
	 */
	public function write($state) {
		return $this->parameters;
	}
}

/**
 * @}
 */

?>