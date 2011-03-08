<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2010 - Boxalino AG
 */


/**
 * Info gateway response
 *
 * @package cem
 * @subpackage client
 */
class CEM_INFO_GatewayResponse extends CEM_GatewayResponse {
	/**
	 * Text response
	 *
	 * @var string
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
	 * @param CEM_GatewayState &$state client state reference
	 * @param string &$data response raw body
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public function read(&$state, &$data) {
		$this->text = $data;
		return TRUE;
	}


	/**
	 * Get text response
	 *
	 * @return string text response
	 */
	public function getText() {
		return $this->text;
	}
}

?>