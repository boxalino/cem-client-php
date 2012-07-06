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
 * Default CEM state handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebStateHandler {
	/**
	 * Encryption facility
	 */
	protected $crypto;

	/**
	 * Cached state
	 */
	protected $state = NULL;


	/**
	 * Constructor
	 *
	 * @param $crypto encryption facility
	 */
	public function __construct($crypto) {
		$this->crypto = $crypto;
	}


	/**
	 * Create client state
	 *
	 * @return client state
	 */
	public function create() {
		return new CEM_GatewayState();
	}

	/**
	 * Read client state from storage
	 *
	 * @return client state or NULL if none
	 */
	public function read() {
		return $this->state;
	}

	/**
	 * Write client state to storage
	 *
	 * @param $state client state
	 */
	public function write($state) {
		$this->state = $state;
	}

	/**
	 * Remove client state from storage
	 *
	 * @param $state client state
	 */
	public function remove($state) {
		$this->state = NULL;
	}
}

/**
 * @}
 */

?>