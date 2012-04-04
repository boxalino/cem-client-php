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
		if (isset($_SERVER['HTTPS'])) {
			$this->parameters['connection'] = ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$this->parameters['clientAddress'] = $_SERVER['REMOTE_ADDR'];
		}
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$this->parameters['clientAgent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if (isset($_SERVER['HTTP_REFERER'])) {
			$this->parameters['clientReferer'] = $_SERVER['HTTP_REFERER'];
		}
		if (isset($_SERVER['SERVER_ADDR'])) {
			$this->parameters['serverAddress'] = $_SERVER['SERVER_ADDR'];
		}
		if (isset($_SERVER['HTTP_HOST'])) {
			$this->parameters['serverHost'] = $_SERVER['HTTP_HOST'];
		}
		if (isset($_SERVER['REQUEST_URI'])) {
			$this->parameters['serverUri'] = $_SERVER['REQUEST_URI'];
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