<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2011 - Boxalino AG
 */


/** PR request/response format: json */
define('CEM_PR_FORMAT_JSON', 'JSON');

/** PR request/response format: xml */
define('CEM_PR_FORMAT_XML', 'XML');


/** GS request/response format: none */
define('CEM_GS_FORMAT_NONE', 'NONE');

/** GS request/response format: json */
define('CEM_GS_FORMAT_JSON', 'JSON');

/** GS request/response format: xml */
define('CEM_GS_FORMAT_XML', 'XML');


/**
 * P&R gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_GatewayRequest14 extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Request format
	 *
	 * @var string
	 */
	protected $requestFormat;

	/**
	 * Requests
	 *
	 * @var array
	 */
	protected $requests;

	/**
	 * Response format
	 *
	 * @var string
	 */
	protected $responseFormat;


	/**
	 * Constructor
	 *
	 * @param string $customer customer identifier
	 */
	public function __construct($customer) {
		parent::__construct();
		$this->customer = $customer;
		$this->requestFormat = CEM_PR_FORMAT_JSON;
		$this->requests = array();
		$this->responseFormat = CEM_PR_FORMAT_JSON;
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
	 * Get recommendation requests
	 *
	 * @return array recommendation requests
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Add recommendation request
	 *
	 * @param CEM_PR_AbstractQuery $request recommendation request
	 */
	public function addRequest($request) {
		$this->requests[] = $request;
	}

	/**
	 * Clear recommendation requests
	 *
	 */
	public function clearRequests() {
		$this->requests = array();
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

		$root = $doc->createElement('cem');
		$root->setAttribute('customer', $this->customer);
		$root->setAttribute('requestFormat', $this->requestFormat);
		$root->setAttribute('responseFormat', $this->responseFormat);

		foreach ($this->requests as $request) {
			$el = $doc->createElement('request');
			if ($this->requestFormat == CEM_GS_FORMAT_JSON) {
				$el->setAttribute('type', $request->type());
				$el->appendChild($doc->createCDATASection(json_encode($request->build($state))));
			} else {
				return FALSE;
			}
			$root->appendChild($el);
		}

		$doc->appendChild($root);

		return $doc->saveXML();
	}
}

/**
 * Abstract recommendation query
 *
 * @package cem
 * @subpackage client
 */
abstract class CEM_PR_AbstractQuery {
	/**
	 * Strategy identifier
	 *
	 * @var string
	 */
	protected $strategy;

	/**
	 * Operation identifier
	 *
	 * @var string
	 */
	protected $operation;

	/**
	 * Strategy parameters
	 *
	 * @var array
	 */
	protected $parameters;


	/**
	 * Constructor
	 *
	 * @param string $strategy strategy identifier
	 * @param string $operation operation identifier
	 */
	public function __construct($strategy, $operation) {
		$this->strategy = $strategy;
		$this->operation = $operation;
		$this->parameters = array();
	}


	/**
	 * Get query type
	 *
	 * @return string query type
	 */
	public function type() {
		return "simple";
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
	 * Get operation identifier
	 *
	 * @return string operation identifier
	 */
	public function getOperation() {
		return $this->operation;
	}

	/**
	 * Get strategy parameter
	 *
	 * @param string $name parameter name
	 * @return string parameter value
	 */
	public function getParameter($name) {
		if (isset($this->parameters[$name])) {
			return $this->parameters[$name];
		}
		return FALSE;
	}

	/**
	 * Set strategy parameter
	 *
	 * @param string $name parameter name
	 * @param string $value parameter value
	 */
	public function setParameter($name, $value) {
		$this->parameters[$name] = $value;
	}

	/**
	 * Remove strategy parameter
	 *
	 * @param string $name parameter name
	 */
	public function removeParameter($name) {
		unset($this->parameters[$name]);
	}

	/**
	 * Clear strategy parameters
	 *
	 */
	public function clearParameters() {
		$this->parameters = array();
	}


	/**
	 * Called to build the query
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return array query
	 */
	public function build(&$state) {
		$parameters = array();
		foreach ($this->parameters as $name => $value) {
			$parameters[] = array(
				"name" => $name,
				"value" => $value
			);
		}
		$indexPreferences = array();
		$context = $state->get('context');
		if (is_array($context)) {
			foreach ($context as $key => $item) {
				if ($key == 'profile') {
					$data = json_decode($item['data']);
					if (isset($data->preferences)) {
						$indexPreferences = $data->preferences;
					}
					break;
				}
			}
		}
		return array(
			'strategy' => $this->strategy,
			'operation' => $this->operation,
			'parameters' => $parameters,
			'indexPreferences' => $indexPreferences
		);
	}
}

/**
 * Query admin query
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_AdminQuery extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 *
	 * @var string
	 */
	protected $index;

	/**
	 * Language identifier
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Base filter
	 *
	 * @var string
	 */
	protected $filter;

	/**
	 * Textual query
	 *
	 * @var string
	 */
	protected $queryText;

	/**
	 * Included properties
	 *
	 * @var array
	 */
	protected $includedProperties;

	/**
	 * Excluded properties
	 *
	 * @var array
	 */
	protected $excludedProperties;
 
	/**
	 * Filter properties
	 *
	 * @var array
	 */
	protected $filterProperties;

	/**
	 * Terms
	 *
	 * @var array
	 */
	protected $queryTerms;


	/**
	 * Constructor
	 *
	 */
	public function __construct($index, $language, $filter, $queryText, $includedProperties = array(), $excludedProperties = array(), $filterProperties = array(), $queryTerms = array()) {
		parent::__construct('kb/query', 'admin');
		$this->index = $index;
		$this->language = $language;
		$this->filter = $filter;
		$this->queryText = $queryText;
		$this->includedProperties = $includedProperties;
		$this->excludedProperties = $excludedProperties;
		$this->filterProperties = $filterProperties;
		$this->queryTerms = $queryTerms;
	}


	/**
	 * Get query type
	 *
	 * @return string query type
	 */
	public function type() {
		return "adminQuery";
	}

	/**
	 * Called to build the query
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return array query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["termPopulation"] = 250;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		$query["filterProperties"] = $this->filterProperties;
		$query["queryTerms"] = $this->queryTerms;
		return $query;
	}
}

/**
 * Query completion recommendation query
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_CompletionQuery extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 *
	 * @var string
	 */
	protected $index;

	/**
	 * Language identifier
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Base filter
	 *
	 * @var string
	 */
	protected $filter;

	/**
	 * Textual query
	 *
	 * @var string
	 */
	protected $queryText;

	/**
	 * Maximum amount of suggestions
	 *
	 * @var int
	 */
	protected $suggestionLimit;
 
	/**
	 * Maximum amount of contextual recommendations
	 *
	 * @var int
	 */
	protected $resultLimit;

	/**
	 * Included properties
	 *
	 * @var array
	 */
	protected $includedProperties;

	/**
	 * Excluded properties
	 *
	 * @var array
	 */
	protected $excludedProperties;
 
	/**
	 * Filter properties
	 *
	 * @var array
	 */
	protected $filterProperties;

	/**
	 * Scorer properties
	 *
	 * @var array
	 */
	protected $scorerProperties;


	/**
	 * Constructor
	 *
	 */
	public function __construct($index, $language, $filter, $queryText, $suggestionLimit, $resultLimit, $includedProperties = array(), $excludedProperties = array(), $filterProperties = array(), $scorerProperties = array()) {
		parent::__construct('kb/query', 'complete');
		$this->index = $index;
		$this->language = $language;
		$this->filter = $filter;
		$this->queryText = $queryText;
		$this->suggestionLimit = $suggestionLimit;
		$this->resultLimit = $resultLimit;
		$this->includedProperties = $includedProperties;
		$this->excludedProperties = $excludedProperties;
		$this->filterProperties = $filterProperties;
		$this->scorerProperties = $scorerProperties;
	}


	/**
	 * Get query type
	 *
	 * @return string query type
	 */
	public function type() {
		return "completeQuery";
	}

	/**
	 * Called to build the query
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return array query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["termPopulation"] = 250;
		$query["suggestionLimit"] = $this->suggestionLimit;
		$query["resultPopulation"] = $this->resultLimit * 10;
		$query["resultLimit"] = $this->resultLimit;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		$query["filterProperties"] = $this->filterProperties;
		$query["scorerProperties"] = $this->scorerProperties;
		return $query;
	}
}


/**
 * Guided-Search gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_GS_GatewayRequest14 extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Dialog identifier
	 *
	 * @var string
	 */
	protected $dialog;

	/**
	 * Language identifier
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Request format
	 *
	 * @var string
	 */
	protected $requestFormat;

	/**
	 * Requests array
	 *
	 * @var array
	 */
	protected $requests;

	/**
	 * Response format
	 *
	 * @var string
	 */
	protected $responseFormat;


	/**
	 * Constructor
	 *
	 * @param string $customer customer identifier
	 * @param string $dialog dialog identifier
	 * @param string $language language identifier
	 */
	public function __construct($customer, $dialog, $language) {
		parent::__construct();
		$this->customer = $customer;
		$this->dialog = $dialog;
		$this->language = $language;
		$this->requestFormat = CEM_GS_FORMAT_JSON;
		$this->responseFormat = CEM_GS_FORMAT_JSON;
		$this->requests = array();
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
	 * Get dialog identifier
	 *
	 * @return string dialog identifier
	 */
	public function getDialog() {
		return $this->dialog;
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
	 * Get request format
	 *
	 * @return string request format
	 */
	public function getRequestFormat() {
		return $this->requestFormat;
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
	 * @param string $action request action (or NULL if none)
	 * @param array $variables request variables hashmap
	 */
	public function insertRequest($action, $variables = array()) {
		array_unshift($this->requests, array('type' => 'request', 'action' => $action, 'variables' => $variables));
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
	 * @param string $action request action (or NULL if none)
	 * @param array $variables request variables hashmap
	 */
	public function appendRequest($action, $variables = array()) {
		$this->requests[] = array('type' => 'action', 'action' => $action, 'variables' => $variables);
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
	 * Get response format
	 *
	 * @return string response format
	 */
	public function getResponseFormat() {
		return $this->responseFormat;
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

		$root = $doc->createElement('cem');
		$root->setAttribute('customer', $this->customer);
		$root->setAttribute('dialog', $this->dialog);
		$root->setAttribute('language', $this->language);
		$root->setAttribute('requestFormat', $this->requestFormat);
		$root->setAttribute('responseFormat', $this->responseFormat);

		if (sizeof($this->requests) > 0) {
			foreach ($this->requests as $request) {
				$el = $doc->createElement($request['type']);
				if (isset($request['action']) && strlen($request['action']) > 0) {
					$el->setAttribute('id', $request['action']);
				}
				if (isset($request['variables'])) {
					if ($this->requestFormat == CEM_GS_FORMAT_JSON) {
						if (sizeof($request['variables']) > 0) {
							$el->appendChild($doc->createCDATASection(json_encode($request['variables'])));
						} else {
							$el->appendChild($doc->createCDATASection("{}"));
						}
					} else {
						return FALSE;
					}
				}
				$root->appendChild($el);
			}
		}

		$context = $state->get('context');
		if (sizeof($context) > 0) {
			foreach ($context as $id => $item) {
				$context = $doc->createElement('context');
				$context->setAttribute('id', $id);
				$context->setAttribute('level', $item['level']);
				$context->setAttribute('mode', $item['mode']);
				$context->appendChild($doc->createCDATASection($item['data']));
				$root->appendChild($context);
			}
		}

		$doc->appendChild($root);

		return $doc->saveXML();
	}
}

?>