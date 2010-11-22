<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2010 - Boxalino AG
 */


/** GS widget modes: raw data */
define('CEM_GS_WIDGET_DATA', 'data');

/** GS widget modes: js source */
define('CEM_GS_WIDGET_SOURCE', 'source');


/**
 * Abstract gateway request
 *
 * @package cem
 * @subpackage client
 */
abstract class CEM_GatewayRequest {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get request body content-type
	 *
	 * @return string request body content-type
	 */
	public abstract function getContentType();

	/**
	 * Called to write the request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return string request raw body
	 */
	public abstract function write(&$state);
}


/**
 * Info gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_INFO_GatewayRequest extends CEM_GatewayRequest {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * Get request body content-type
	 *
	 * @return string request body content-type
	 */
	public function getContentType() {
		return NULL;
	}

	/**
	 * Called to write the request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return string request raw body
	 */
	public function write(&$state) {
		return NULL;
	}
}


/**
 * Guided-Search gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_GS_SimpleRequest extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Machine identifier
	 *
	 * @var string
	 */
	protected $machine;

	/**
	 * Language identifier
	 *
	 * @var string
	 */
	protected $language;

	/**
	 *  Response flag
	 *
	 * @var boolean
	 */
	protected $response;

	/**
	 * Requests array
	 *
	 * @var array
	 */
	protected $requests;

	/**
	 * Widgets array
	 *
	 * @var array
	 */
	protected $widgets;


	/**
	 * Constructor
	 *
	 * @param string $customer customer identifier
	 * @param string $machine machine identifier
	 * @param string $language language identifier
	 * @param boolean $response response flag
	 */
	public function __construct($customer, $machine, $language, $response = FALSE) {
		parent::__construct();
		$this->customer = $customer;
		$this->machine = $machine;
		$this->language = $language;
		$this->response = $response;
		$this->requests = array();
		$this->widgets = array();
	}


	/**
	 * Get customer identifier
	 *
	 * @return string customer identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * Get machine identifier
	 *
	 * @return string machine identifier
	 */
	public function getMachine() {
		return $this->machine;
	}

	/**
	 * Get language identifier
	 *
	 * @return string language identifier
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Get response flag
	 *
	 * @return boolean response flag
	 */
	public function hasResponse() {
		return $this->response;
	}


	/**
	 * Get request batch
	 *
	 * @return array request batch
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Insert a new request at the begining of the batch
	 *
	 * @param string $jump request jump (or NULL if none)
	 * @param array $variables request variables hashmap
	 */
	public function insertRequest($jump, $variables = array()) {
		array_unshift($this->requests, array('type' => 'request', 'jump' => $jump, 'variables' => $variables));
	}

	/**
	 * Insert a new init request at the begining of the batch
	 *
	 */
	public function insertInitRequest() {
		array_unshift($this->requests, array('type' => 'init'));
	}

	/**
	 * Insert a new free request at the begining of the batch
	 *
	 */
	public function insertFreeRequest() {
		array_unshift($this->requests, array('type' => 'free'));
	}

	/**
	 * Insert a new request at the end of the batch
	 *
	 * @param string $jump request jump (or NULL if none)
	 * @param array $variables request variables hashmap
	 */
	public function appendRequest($jump, $variables = array()) {
		$this->requests[] = array('type' => 'request', 'jump' => $jump, 'variables' => $variables);
	}

	/**
	 * Insert a new init request at the begining of the batch
	 *
	 */
	public function appendInitRequest() {
		$this->requests[] = array('type' => 'init');
	}

	/**
	 * Insert a new free request at the begining of the batch
	 *
	 */
	public function appendFreeRequest() {
		$this->requests[] = array('type' => 'free');
	}

	/**
	 * Clear request batch
	 *
	 */
	public function clearRequests() {
		$this->requests = array();
	}


	/**
	 * Get requested widgets
	 *
	 * @return array request widgets
	 */
	public function getWidgets() {
		return array_keys($this->widgets);
	}

	/**
	 * Get requested widget's instance
	 *
	 * @param string $templateId template identifier
	 * @return array requested widget's instances
	 */
	public function getWidgetInstances($templateId) {
		if (isset($this->widgets[$templateId])) {
			return $this->widgets[$templateId];
		}
		return array();
	}

	/**
	 * Add widget's instance to requested list
	 *
	 * @param string $templateId template identifier
	 * @param string $instanceId instance identifier
	 * @param string $mode widget mode (CEM_GS_WIDGET_DATA or CEM_GS_WIDGET_SOURCE)
	 */
	public function addWidget($templateId, $instanceId, $mode = CEM_GS_WIDGET_DATA) {
		if (!isset($this->widgets[$templateId])) {
			$this->widgets[$templateId] = array();
		}
		$this->widgets[$templateId][$instanceId] = $mode;
	}

	/**
	 * Remove widget's instance from requested list
	 *
	 * @param string $templateId template identifier
	 * @param string $instanceId instance identifier
	 */
	public function removeWidget($templateId, $instanceId) {
		if (isset($this->widgets[$templateId])) {
			unset($this->widgets[$templateId][$instanceId]);
		}
	}

	/**
	 * Remove widget's instances from requested list
	 *
	 * @param string $templateId template identifier
	 */
	public function clearWidget($templateId) {
		unset($this->widgets[$templateId]);
	}

	/**
	 * Remove all widget's instances from requested list
	 *
	 */
	public function clearWidgets() {
		$this->widgets = array();
	}


	/**
	 * Get request body content-type
	 *
	 * @return string request body content-type
	 */
	public function getContentType() {
		return "text/xml; charset=UTF-8";
	}

	/**
	 * Called to write the request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return string request raw body
	 */
	public function write(&$state) {
		$doc = new DOMDocument("1.0", 'UTF-8');

		$root = $doc->createElement('cem-request');
		$root->setAttribute('customer', $this->customer);
		$root->setAttribute('machine', $this->machine);
		$root->setAttribute('language', $this->language);

		$context = $state->get('context');
		if (is_array($context) > 0) {
			foreach ($context as $id => $item) {
				$context = $doc->createElement('context');
				$context->setAttribute('id', $id);
				$context->setAttribute('level', $item['level']);
				$context->setAttribute('mode', $item['mode']);
				$context->appendChild($doc->createCDATASection($item['data']));
				$root->appendChild($context);
			}
		} else if (sizeof($this->requests) == 0 || $this->requests[0]['type'] != 'init') {
			$this->insertInitRequest();
		}

		if (sizeof($this->requests) > 0) {
			$requests = $doc->createElement('requests');
			$requests->setAttribute('response', $this->response ? 'true' : 'false');
			foreach ($this->requests as $request) {
				$el = $doc->createElement($request['type']);
				if (isset($request['jump']) && strlen($request['jump']) > 0) {
					$el->setAttribute('jump', $request['jump']);
				}
				if (isset($request['variables'])) {
					if (sizeof($request['variables']) > 0) {
						$el->appendChild($doc->createCDATASection(json_encode($request['variables'])));
					} else {
						$el->appendChild($doc->createCDATASection("{}"));
					}
				}
				$requests->appendChild($el);
			}
			$root->appendChild($requests);
		}

		if (sizeof($this->widgets) > 0) {
			$widgets = $doc->createElement('widgets');
			foreach ($this->widgets as $templateId => $instances) {
				foreach ($instances as $instanceId => $mode) {
					$el = $doc->createElement('widget');
					$el->setAttribute('template', $templateId);
					$el->setAttribute('widget', $instanceId);
					$el->setAttribute('mode', $mode);
					$widgets->appendChild($el);
				}
			}
			$root->appendChild($widgets);
		}

		$doc->appendChild($root);

		return $doc->saveXML();
	}
}

?>