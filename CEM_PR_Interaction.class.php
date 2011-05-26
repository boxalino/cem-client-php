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
 * PR interaction for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_Interaction extends CEM_AbstractWebHandler {
	/**
	 * Current request
	 */
	protected $request;

	/**
	 * Current response
	 */
	protected $response;

	/**
	 * User-defined options
	 */
	protected $options;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param &$request client request reference
	 * @param &$response client response reference
	 * @param &$options user-defined options
	 */
	public function __construct(&$crypto, &$request, &$response, &$options) {
		parent::__construct($crypto);
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
	}


	/**
	 * Get underlying request
	 *
	 * @return underlying request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Get underlying response
	 *
	 * @return underlying response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get user-defined options
	 *
	 * @return user-defined options
	 */
	public function getOptions() {
		return $this->options;
	}


	/**
	 * Get server version
	 *
	 * @return server version
	 */
	public function getVersion() {
		return $this->response->getVersion();
	}

	/**
	 * Get status
	 *
	 * @return status
	 */
	public function getStatus() {
		return $this->response->getStatus();
	}

	/**
	 * Get response message
	 *
	 * @return response message
	 */
	public function getMessage() {
		return $this->response->getMessage();
	}

	/**
	 * Get time
	 *
	 * @return time (in seconds)
	 */
	public function getTime() {
		return $this->response->getTime();
	}


	/**
	 * Get recommendations
	 *
	 * @return recommendations
	 */
	public function getRecommendations() {
		return $this->response->getResponses();
	}
}

/**
 * @}
 */

?>