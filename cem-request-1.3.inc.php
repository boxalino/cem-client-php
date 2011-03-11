<?php

/**
 * @internal
 *
 * Boxalino CEM client library in PHP.
 *
 * (C) 2009-2011 - Boxalino AG
 */


/** P&R parameter types: boolean */
define('CEM_PR_TYPE_BOOLEAN',  'boolean');

/** P&R parameter types: array of boolean */
define('CEM_PR_TYPE_BOOLEANS', 'boolean[]');

/** P&R parameter types: date */
define('CEM_PR_TYPE_DATE',     'date');

/** P&R parameter types: array of date */
define('CEM_PR_TYPE_DATES',    'date[]');

/** P&R parameter types: identifier */
define('CEM_PR_TYPE_ID',       'id');

/** P&R parameter types: array of identifier */
define('CEM_PR_TYPE_IDS',      'id[]');

/** P&R parameter types: matches */
define('CEM_PR_TYPE_MATCHES',  'matches');

/** P&R parameter types: number */
define('CEM_PR_TYPE_NUMBER',   'numeric');

/** P&R parameter types: array of number */
define('CEM_PR_TYPE_NUMBERS',  'numeric[]');

/** P&R parameter types: search */
define('CEM_PR_TYPE_SEARCH',   'search');

/** P&R parameter types: array of search */
define('CEM_PR_TYPE_SEARCHES', 'search[]');

/** P&R parameter types: string */
define('CEM_PR_TYPE_STRING',   'string');

/** P&R parameter types: array of string */
define('CEM_PR_TYPE_STRINGS',  'string[]');

/** P&R parameter types: localized text */
define('CEM_PR_TYPE_TEXT',     'text');

/** P&R parameter types: array of localized text */
define('CEM_PR_TYPE_TEXTS',    'text[]');


/** GS widget modes: raw data */
define('CEM_GS_WIDGET_DATA', 'data');

/** GS widget modes: js source */
define('CEM_GS_WIDGET_SOURCE', 'source');


/**
 * P&R gateway request
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_SimpleRequest extends CEM_GatewayRequest {
	/**
	 * Strategy identifier
	 *
	 * @var string
	 */
	protected $strategy;

	/**
	 * Offset
	 *
	 * @var integer
	 */
	protected $offset;

	/**
	 * Size
	 *
	 * @var integer
	 */
	protected $size;

	/**
	 * Context
	 *
	 * @var array
	 */
	protected $context;


	/**
	 * Constructor
	 *
	 * @param string $strategy strategy identifier
	 * @param integer $offset offset
	 * @param integer $size size
	 */
	public function __construct($strategy, $offset, $size) {
		parent::__construct();
		$this->strategy = $strategy;
		$this->offset = $offset;
		$this->size = $size;
		$this->context = array();
	}


	/**
	 * Get strategy identifier
	 *
	 * @return string strategy identifier
	 */
	public function getStrategy() {
		return $this->strategy;
	}

	/**
	 * Get offset
	 *
	 * @return integer offset
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Get size
	 *
	 * @return integer size
	 */
	public function getSize() {
		return $this->size;
	}


	/**
	 * Get recommendation context
	 *
	 * @return array recommendation context
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Set recommendation context parameter
	 *
	 * @param string $key parameter key
	 * @param string $type parameter type (CEM_PR_TYPE_*)
	 * @param mixed $value parameter value (raw value depends of type)
	 */
	public function setContext($key, $type, $value) {
		$this->context[$key] = array(
			'type' => $type,
			'value' => $value
		);
	}

	/**
	 * Set recommendation context parameter (boolean)
	 *
	 * @param string $key parameter key
	 * @param boolean $value parameter value
	 */
	public function setBoolean($key, $value) {
		if (!is_bool($value)) {
			$value = $value ? TRUE : FALSE;
		}
		$this->setContext($key, CEM_PR_TYPE_BOOLEAN, $value);
	}

	/**
	 * Set recommendation context parameter (boolean[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setBooleans($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_bool($value[$k])) {
					$value[$k] = $value[$k] ? TRUE : FALSE;
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_BOOLEANS, $value);
	}

	/**
	 * Set recommendation context parameter (date)
	 *
	 * @param string $key parameter key
	 * @param date $value parameter value
	 */
	public function setDate($key, $value) {
		if (!is_string($value)) {
			$value = strval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_DATE, $value);
	}

	/**
	 * Set recommendation context parameter (date[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setDates($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_string($value[$k])) {
					$value[$k] = strval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_DATES, $value);
	}

	/**
	 * Set recommendation context parameter (id)
	 *
	 * @param string $key parameter key
	 * @param string $value parameter value
	 */
	public function setId($key, $value) {
		if (!is_string($value)) {
			$value = strval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_ID, $value);
	}

	/**
	 * Set recommendation context parameter (id[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setIds($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_string($value[$k])) {
					$value[$k] = strval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_IDS, $value);
	}

	/**
	 * Set recommendation context parameter (matches)
	 *
	 * @param string $key parameter key
	 * @param object $value parameter value
	 */
	public function setMatches($key, $value) {
		// TODO: type checks
		$this->setContext($key, CEM_PR_TYPE_MATCHES, $value);
	}

	/**
	 * Set recommendation context parameter (numeric)
	 *
	 * @param string $key parameter key
	 * @param float $value parameter value
	 */
	public function setNumber($key, $value) {
		if (!is_numeric($value)) {
			$value = floatval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_NUMBER, $value);
	}

	/**
	 * Set recommendation context parameter (numeric[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setNumbers($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_numeric($value[$k])) {
					$value[$k] = floatval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_NUMBERS, $value);
	}

	/**
	 * Set recommendation context parameter (search)
	 *
	 * @param string $key parameter key
	 * @param object $value parameter value
	 */
	public function setSearch($key, $value) {
		// TODO: type checks
		$this->setContext($key, CEM_PR_TYPE_SEARCH, $value);
	}

	/**
	 * Set recommendation context parameter (search[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setSearches($key, $value) {
		if (is_array($value)) {
			// TODO: type checks
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_SEARCHES, $value);
	}

	/**
	 * Set recommendation context parameter (string)
	 *
	 * @param string $key parameter key
	 * @param string $value parameter value
	 */
	public function setString($key, $value) {
		if (!is_string($value)) {
			$value = strval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_STRING, $value);
	}

	/**
	 * Set recommendation context parameter (string[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setStrings($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_string($value[$k])) {
					$value[$k] = strval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_STRINGS, $value);
	}

	/**
	 * Set recommendation context parameter (text)
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setText($key, $value) {
		if (is_array($value)) {
			// TODO: type checks
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_TEXT, $value);
	}

	/**
	 * Set recommendation context parameter (text[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setTexts($key, $value) {
		if (is_array($value)) {
			// TODO: type checks
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_TEXTS, $value);
	}

	/**
	 * Clear recommendation context
	 *
	 */
	public function clearContext() {
		$this->context = array();
	}


	/**
	 * Get request body content-type
	 *
	 * @return string request body content-type
	 */
	public function getContentType() {
		return "text/plain; charset=UTF-8";
	}

	/**
	 * Called to write the request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return string request raw body
	 */
	public function write(&$state) {
		$profile = $state->get("ctx_profile");

		$root = array();
		$root['strategy'] = $this->strategy;
		$root['offset'] = $this->offset;
		$root['size'] = $this->size;
		$root['context'] = $this->context;
		if ($profile) {
			$root['profile'] = json_decode($profile);
		}
		return json_encode($root);
	}
}


/**
 * Guided-Search gateway request
 *
 * @author nitro@boxalino.com
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