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
 * Abstract gateway response
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_GatewayResponse {
	/**
	 * Server error
	 */
	protected $transport = NULL;

	/**
	 * Server version
	 */
	protected $version = '';

	/**
	 * Response status
	 */
	protected $status = FALSE;

	/**
	 * Response message
	 */
	protected $message = '';

	/**
	 * Response time
	 */
	protected $time = 0;

	/**
	 * Cryptographic parameters
	 */
	protected $crypto = array('key' => '', 'iv' => '');


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get processing time
	 *
	 * @return processing time (in seconds)
	 */
	public function getTotalTime() {
		return isset($this->transport['time']) ? $this->transport['time'] : 0;
	}

	/**
	 * Get server transport information
	 *
	 * @return server transport information
	 */
	public function getTransport() {
		return $this->transport;
	}

	/**
	 * Called to set server transport information
	 *
	 * @param $code http code
	 * @param $message http error message
	 * @param $time total transport time
	 * @param $data body data
	 */
	public function setTransport($code, $message, $time, $data) {
		$this->transport = array(
			'code' => $code,
			'message' => $message,
			'time' => $time,
			'data' => $data
		);
	}


	/**
	 * Get server version
	 *
	 * @return server version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Get status
	 *
	 * @return status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Get response message
	 *
	 * @return response message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Get remote time
	 *
	 * @return remote time (in seconds)
	 */
	public function getTime() {
		return ($this->time / 1000.0);
	}

	/**
	 * Get cryptographic engine
	 *
	 * @return cryptographic engine
	 */
	public function getCrypto() {
		return new CEM_WebEncryption($this->crypto['key'], $this->crypto['iv']);
	}


	/**
	 * Called to read the response
	 *
	 * @param &$state client state reference
	 * @param &$data response raw body
	 * @return TRUE on success, FALSE otherwise
	 */
	public abstract function read(&$state, &$data);
}

/**
 * @}
 */

?>