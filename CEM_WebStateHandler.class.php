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
class CEM_WebStateHandler extends CEM_AbstractWebHandler {
	/**
	 * Cached state
	 */
	protected $state;

	/**
	 * Json decoded contexts (cache)
	 */
	private $_jsonContexts = array();


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 */
	public function __construct(&$crypto) {
		parent::__construct($crypto);
		$this->state = NULL;
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
	 * @param &$state client state
	 */
	public function write(&$state) {
		$this->state = $state;
	}

	/**
	 * Remove client state from storage
	 *
	 * @param &$state client state
	 */
	public function remove(&$state) {
		$this->state = NULL;
	}


	/**
	 * Get context scopes
	 *
	 * @return context scopes
	 */
	public function getContext() {
		$state = $this->read();
		if (!$state) {
			$state = $this->create();
		}
		return ($state ? $state->get('context', array()) : array());
	}

	/**
	 * Get context data
	 *
	 * @param $name context name
	 * @return context data
	 */
	public function getContextData($name) {
		$scopes = $this->getContext();
		if (isset($scopes[$name])) {
			return $scopes[$name]['data'];
		}
		return '';
	}

	/**
	 * Get context data from json
	 *
	 * @param $name context name
	 * @return context data (decoded)
	 */
	public function getContextJson($name) {
		if (!isset($this->_jsonContexts[$name])) {
			$this->_jsonContexts[$name] = @json_decode($this->getContextData($name));
		}
		return $this->_jsonContexts[$name];
	}
}

/**
 * @}
 */

?>