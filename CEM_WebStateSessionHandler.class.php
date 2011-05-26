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
 * Session-based CEM state handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebStateSessionHandler extends CEM_WebStateHandler {
	/**
	 * State variable key
	 */
	protected $name;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param $name variable name (defaults to 'cem')
	 */
	public function __construct(&$crypto, $name = 'cem') {
		parent::__construct($crypto);
		$this->name = $name;

		// start session if necessary
		if (strlen(session_id()) == 0) {
			session_start();
		}
		if (isset($_SESSION[$this->name])) {
			$this->state = $_SESSION[$this->name];
		}
	}


	/**
	 * Write client state to storage
	 *
	 * @param &$state client state
	 */
	public function write(&$state) {
		$_SESSION[$this->name] = $state;

		parent::write($state);
	}

	/**
	 * Remove client state from storage
	 *
	 * @param &$state client state
	 */
	public function remove(&$state) {
		if (isset($_SESSION[$this->name])) {
			unset($_SESSION[$this->name]);
		}

		parent::remove($state);
	}
}

/**
 * @}
 */

?>