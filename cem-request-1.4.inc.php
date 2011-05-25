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
 * @author nitro@boxalino.com
 */
class CEM_PR_GatewayRequest14 extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 */
	protected $customer;

	/**
	 * Request format
	 */
	protected $requestFormat;

	/**
	 * Requests
	 */
	protected $requests;

	/**
	 * Response format
	 */
	protected $responseFormat;


	/**
	 * Constructor
	 *
	 * @param $customer customer identifier
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
	 * @return customer identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * Get recommendation requests
	 *
	 * @return recommendation requests
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Add recommendation request
	 *
	 * @param $request recommendation request
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
	 * @return request body content-type
	 */
	public function getContentType() {
		return "text/xml; charset=UTF-8";
	}

	/**
	 * Called to write the request
	 *
	 * @param &$state client state reference
	 * @return request raw body
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
 * @author nitro@boxalino.com
 */
abstract class CEM_PR_AbstractQuery {
	/**
	 * Strategy identifier
	 */
	protected $strategy;

	/**
	 * Operation identifier
	 */
	protected $operation;

	/**
	 * Personalized flag
	 */
	protected $personalized;
	

	/**
	 * Constructor
	 *
	 * @param $strategy strategy identifier
	 * @param $operation operation identifier
	 * @param $personalized personalized flag
	 */
	public function __construct($strategy, $operation, $personalized = TRUE) {
		$this->strategy = $strategy;
		$this->operation = $operation;
		$this->personalized = $personalized;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "simple";
	}

	/**
	 * Get strategy identifier
	 *
	 * @return strategy identifier
	 */
	public function getStrategy() {
		return $this->strategy;
	}
 
	/**
	 * Get operation identifier
	 *
	 * @return operation identifier
	 */
	public function getOperation() {
		return $this->operation;
	}


	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		if ($this->personalized) {
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
				'indexPreferences' => $indexPreferences
			);
		}
		return array(
			'strategy' => $this->strategy,
			'operation' => $this->operation
		);
	}
}

/**
 * Query admin query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_AdminQuery extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 */
	protected $index;

	/**
	 * Language identifier
	 */
	protected $language;

	/**
	 * Base filter
	 */
	protected $filter;

	/**
	 * Textual query
	 */
	protected $queryText;

	/**
	 * Included properties
	 */
	protected $includedProperties;

	/**
	 * Excluded properties
	 */
	protected $excludedProperties;
 
	/**
	 * Filter properties
	 */
	protected $filterProperties;

	/**
	 * Terms
	 */
	protected $queryTerms;


	/**
	 * Constructor
	 *
	 * @param $index index identifier
	 * @param $language language identifier
	 * @param $filter query filter
	 * @param $queryText query text
	 * @param $includedProperties included properties
	 * @param $excludedProperties excluded properties
	 * @param $filterProperties filter properties
	 * @param $queryTerms terms to update
	 */
	public function __construct($index, $language, $filter, $queryText, $includedProperties = array(), $excludedProperties = array(), $filterProperties = array(), $queryTerms = array()) {
		parent::__construct('kb/query', 'admin', FALSE);
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
	 * @return query type
	 */
	public function type() {
		return "adminQuery";
	}

	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["termPopulation"] = 100;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		$query["filterProperties"] = $this->filterProperties;
		$query["queryTerms"] = $this->queryTerms;
		return $query;
	}
}

/**
 * Search detail admin query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_AdminSearchDetail extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 */
	protected $index;

	/**
	 * Property values
	 */
	protected $properties;


	/**
	 * Constructor
	 *
	 * @param $index index identifier
	 * @param $language language identifier
	 * @param $properties property values
	 */
	public function __construct($index, $properties) {
		parent::__construct('kb/search', 'adminDetail', FALSE);
		$this->index = $index;
		$this->properties = $properties;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "adminDetail";
	}

	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["configuration"] = array('properties' => $this->properties);
		return $query;
	}
}

/**
 * Query completion recommendation query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_CompletionQuery extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 */
	protected $index;

	/**
	 * Language identifier
	 */
	protected $language;

	/**
	 * Base filter
	 */
	protected $filter;

	/**
	 * Textual query
	 */
	protected $queryText;

	/**
	 * Maximum amount of suggestions
	 */
	protected $suggestionLimit;
 
	/**
	 * Maximum amount of contextual recommendations
	 */
	protected $resultLimit;

	/**
	 * Included properties
	 */
	protected $includedProperties;

	/**
	 * Excluded properties
	 */
	protected $excludedProperties;
 
	/**
	 * Filter properties
	 */
	protected $filterProperties;

	/**
	 * Scorer properties
	 */
	protected $scorerProperties;


	/**
	 * Constructor
	 *
	 * @param $index index identifier
	 * @param $language language identifier
	 * @param $filter query filter
	 * @param $queryText query text
	 * @param $suggestionLimit suggestion limit
	 * @param $resultLimit result limit
	 * @param $includedProperties included properties
	 * @param $excludedProperties excluded properties
	 * @param $filterProperties filter properties
	 * @param $scorerProperties scorer properties
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
	 * @return query type
	 */
	public function type() {
		return "completeQuery";
	}

	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["suggestionLimit"] = $this->suggestionLimit;
		$query["resultPopulation"] = $this->resultLimit * 2;
		$query["resultLimit"] = $this->resultLimit;
		$query["termPopulation"] = 100;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		$query["filterProperties"] = $this->filterProperties;
		$query["scorerProperties"] = $this->scorerProperties;
		return $query;
	}
}

/**
 * Guidance previews query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_GuidancePreviews extends CEM_PR_AbstractQuery {
	/**
	 * Base query
	 */
	protected $query;

	/**
	 * Textual query
	 */
	protected $queryText;

	/**
	 * Terms
	 */
	protected $queryTerms;

	/**
	 * Guidances
	 */
	protected $guidances;

	/**
	 * Filter properties
	 */
	protected $filterProperties;

	/**
	 * Scorer properties
	 */
	protected $scorerProperties;

	/**
	 * Refinements
	 */
	protected $refinements;

	/**
	 * Attributes
	 */
	protected $attributes;

	/**
	 * Maximum recommendations
	 */
	protected $maximumRecommendations;

	/**
	 * Alternatives flag
	 */
	protected $alternatives;


	/**
	 * Constructor
	 *
	 * @param $query base query
	 * @param $queryText query text
	 * @param $queryTerms query terms
	 * @param $guidances guidances
	 * @param $filterProperties filter properties
	 * @param $scorerProperties scorer properties
	 * @param $refinements refinements
	 * @param $attributes attributes
	 * @param $maximumRecommendations maximum recommendations
	 * @param $alternatives alternatives flag
	 */
	public function __construct($query, $queryText, $queryTerms, $guidances, $filterProperties, $scorerProperties, $refinements, $attributes, $maximumRecommendations, $alternatives) {
		parent::__construct('kb/guidance', 'previews', FALSE);
		$this->query = $query;
		$this->queryText = $queryText;
		$this->queryTerms = $queryTerms;
		$this->guidances = $guidances;
		$this->filterProperties = $filterProperties;
		$this->scorerProperties = $scorerProperties;
		$this->refinements = $refinements;
		$this->attributes = $attributes;
		$this->maximumRecommendations = $maximumRecommendations;
		$this->alternatives = $alternatives;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "guidancePreviews";
	}

	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["query"] = $this->query;
		$query["queryText"] = $this->queryText;
		$query["queryTerms"] = $this->queryTerms;
		$query["guidances"] = $this->guidances;
		$query["filterProperties"] = $this->filterProperties;
		$query["scorerProperties"] = $this->scorerProperties;
		$query["refinements"] = $this->refinements;
		$query["attributes"] = $this->attributes;
		$query["maximumRecommendations"] = $this->maximumRecommendations;
		$query["alternatives"] = $this->alternatives;
		return $query;
	}
}


/**
 * Guided-Search gateway request
 *
 * @author nitro@boxalino.com
 */
class CEM_GS_GatewayRequest14 extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 */
	protected $customer;

	/**
	 * Dialog identifier
	 */
	protected $dialog;

	/**
	 * Language identifier
	 */
	protected $language;

	/**
	 * Request format
	 */
	protected $requestFormat;

	/**
	 * Requests array
	 */
	protected $requests;

	/**
	 * Response format
	 */
	protected $responseFormat;


	/**
	 * Constructor
	 *
	 * @param $customer customer identifier
	 * @param $dialog dialog identifier
	 * @param $language language identifier
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
	 * @return customer identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * Get dialog identifier
	 *
	 * @return dialog identifier
	 */
	public function getDialog() {
		return $this->dialog;
	}

	/**
	 * Get language identifier
	 *
	 * @return language identifier
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Get request format
	 *
	 * @return request format
	 */
	public function getRequestFormat() {
		return $this->requestFormat;
	}

	/**
	 * Get request batch
	 *
	 * @return request batch
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Insert a new request at the begining of the batch
	 *
	 * @param $action request action (or NULL if none)
	 * @param $variables request variables hashmap
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
	 * @param $action request action (or NULL if none)
	 * @param $variables request variables hashmap
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
	 * @return response format
	 */
	public function getResponseFormat() {
		return $this->responseFormat;
	}


	/**
	 * Get request body content-type
	 *
	 * @return request body content-type
	 */
	public function getContentType() {
		return "text/xml; charset=UTF-8";
	}

	/**
	 * Called to write the request
	 *
	 * @param &$state client state reference
	 * @return request raw body
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

/**
 * @}
 */

?>