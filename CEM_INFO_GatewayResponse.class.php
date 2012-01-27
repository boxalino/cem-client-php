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
 * Info gateway response
 *
 * @author nitro@boxalino.com
 */
class CEM_INFO_GatewayResponse extends CEM_GatewayResponse {
	/**
	 * Text response
	 */
	protected $text;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->text = NULL;
	}


	/**
	 * Called to read the response
	 *
	 * @param &$state client state reference
	 * @param &$data response raw body
	 * @return TRUE on success, FALSE otherwise
	 */
	public function read(&$state, &$data) {
		$this->text = $data;
		return TRUE;
	}


	/**
	 * Get text response
	 *
	 * @return text response
	 */
	public function getText() {
		return $this->text;
	}
}

/**
 * @}
 */

?>