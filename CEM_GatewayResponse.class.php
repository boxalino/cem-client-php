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
	 * Constructor
	 *
	 */
	public function __construct() {
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
	 * Get time
	 *
	 * @return time (in seconds)
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