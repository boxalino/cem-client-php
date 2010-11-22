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
 * Abstract gateway response
 *
 * @package cem
 * @subpackage client
 */
abstract class CEM_GatewayResponse {
	/**
	 * Processing time
	 *
	 * @var float
	 */
	protected $time;	


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get processing time
	 *
	 * @return float processing time (in seconds)
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * Called to set processing time
	 *
	 * @param float $time processing time (in seconds)
	 */
	public function setTime($time) {
		$this->time = $time;
	}

	/**
	 * Called to read the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param string &$data response raw body
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public abstract function read(&$state, &$data);
}


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


/**
 * P&R gateway response
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_SimpleResponse extends CEM_GatewayResponse {
	/**
	 * Json response
	 *
	 * @var string
	 */
	protected $json;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->json = NULL;
	}


	/**
	 * Called to read the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param string &$data response raw body
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public function read(&$state, &$data) {
		$this->json = $data;
		return TRUE;
	}


	/** 
	 * Get raw json response
	 *
	 * @return string raw json response
	 */
	public function getJson() {
		return $this->json;
	}
}


/**
 * Guided-Search gateway response
 *
 * @package cem
 * @subpackage client
 */
class CEM_GS_SimpleResponse extends CEM_GatewayResponse {
	/**
	 * Customer identifier
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Language identifier
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
	 * Context scopes
	 *
	 * @var array
	 */
	protected $context;

	/**
	 * Response scope
	 *
	 * @var string
	 */
	protected $response;

	/**
	 * Binding scope
	 *
	 * @var string
	 */
	protected $bindings;

	/**
	 * Widgets array
	 *
	 * @var array
	 */
	protected $widgets;

	/**
	 * Response size
	 *
	 * @var int
	 */
	protected $size;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->customer = NULL;
		$this->machine = NULL;
		$this->language = NULL;
		$this->context = array();
		$this->response = NULL;
		$this->bindings = NULL;
		$this->widgets = array();
		$this->size = 0;
	}


	/**
	 * Get response size
	 *
	 * @return string response size
	 */
	public function getSize() {
		return $this->size;
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
	 * Get context scope names
	 *
	 * @return array context scope names
	 */
	public function getContextScopes() {
		return array_keys($this->context);
	}

	/**
	 * Get context scope
	 *
	 * @param string $name context name
	 * @return array context scope or NULL if not found
	 */
	public function getContextScope($name) {
		if (isset($this->context[$name])) {
			return $this->context[$name];
		}
		return NULL;
	}

	/**
	 * Get response scope
	 *
	 * @return string response scope
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get bindings scope
	 *
	 * @return string bindings scope
	 */
	public function getBindings() {
		return $this->bindings;
	}


	/**
	 * Get widgets instances
	 *
	 * @return array widget instances (templateId, instanceId)
	 */
	public function getWidgets() {
		$list = array();
		foreach ($this->widgets as $templateId => $instances) {
			foreach ($instances as $instanceId => $data) {
				$list[] = array($templateId, $instanceId);
			}
		}
		return $list;
	}

	/**
	 * Get widget data
	 *
	 * @param string $templateId template identifier
	 * @param string $instanceId instance identifier
	 * @return string widget data or "" if none
	 */
	public function getWidget($templateId, $instanceId) {
		if (isset($this->widgets[$templateId]) && isset($this->widgets[$templateId][$instanceId])) {
			return $this->widgets[$templateId][$instanceId];
		}
		return "";
	}


	/**
	 * Called to read the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param string &$data response raw body
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public function read(&$state, &$data) {
		$this->size = strlen($data);
		$doc = new DOMDocument("1.0", 'UTF-8');
		$doc->loadXML($data);
		if ($this->visitResponse($doc->documentElement)) {
			$state->set('context', $this->context);
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Visit xml response
	 *
	 * @param object &$root root element
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	protected function visitResponse(&$node) {
		// check root element
		if ($node->tagName != 'cem-response') {
			return FALSE;
		}

		// get attributes
		$this->customer = $node->getAttribute('customer');
		$this->machine = $node->getAttribute('machine');
		$this->language = $node->getAttribute('language');

		// visit children
		$this->context = array();
		$this->response = NULL;
		$this->bindings = NULL;
		$this->widgets = array();
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'context':
					$this->context[$child->getAttribute('id')] = array(
						'level' => $child->getAttribute('level'),
						'mode' => $child->getAttribute('mode'),
						'data' => $this->visitTexts($child)
					);
					break;

				case 'response':
					$this->response = $this->visitTexts($child);
					break;

				case 'bindings':
					$this->bindings = $this->visitTexts($child);
					break;

				case 'widgets':
					if (!$this->visitWidgets($child)) {
						return FALSE;
					}
					break;
				}
				break;
			}
		}
		return TRUE;
	}

	/**
	 * Visit xml response
	 *
	 * @param object &$node xml element
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	protected function visitWidgets(&$node) {
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'widget':
					$this->widgets[$child->getAttribute('template')][$child->getAttribute('widget')] = $this->visitTexts($child);
					break;
				}
				break;
			}
		}
		return TRUE;
	}

	/**
	 * Visit xml text nodes
	 *
	 * @param object &$node xml element
	 * @return string text content
	 */
	protected function visitTexts(&$node) {
		$text = '';
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_TEXT_NODE:
			case XML_CDATA_SECTION_NODE:
				$text .= $child->data;
				break;
			}
		}
		return $text;
	}
}

?>