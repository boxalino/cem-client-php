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
	 * Server version
	 */
	protected $version;

	/**
	 * Response status
	 */
	protected $status;

	/**
	 * Response message
	 */
	protected $message;

	/**
	 * Response time
	 */
	protected $time;

	/**
	 * Processing time
	 */
	protected $totalTime;

	/**
	 * Cryptographic parameters
	 */
	protected $crypto;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->version = '';
		$this->status = FALSE;
		$this->message = '';
		$this->time = 0;
		$this->totalTime = 0;
		$this->crypto = array(
			'key' => '',
			'iv' => ''
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
	 * Get processing time
	 *
	 * @return processing time (in seconds)
	 */
	public function getTotalTime() {
		return $this->totalTime;
	}

	/**
	 * Called to set processing time
	 *
	 * @param $time processing time (in seconds)
	 */
	public function setTotalTime($time) {
		$this->totalTime = $time;
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