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
 * CEM controller for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebController {
	/**
	 * Encryption facility
	 */
	protected $crypto;

	/**
	 * CEM server url
	 */
	protected $url;

	/**
	 * Customer account (defaults to 'default')
	 */
	protected $customer;

	/**
	 * Index (defaults to 'default')
	 */
	protected $index;

	/**
	 * Language (defaults to 'en')
	 */
	protected $language;

	/**
	 * Dialog (defaults to 'search')
	 */
	protected $dialog;

	/**
	 * State handler
	 */
	protected $stateHandler;

	/**
	 * Request handler
	 */
	protected $requestHandler;

	/**
	 * Response handler
	 */
	protected $responseHandler;

	/**
	 * Connection timeout [ms]
	 */
	protected $connectionTimeout;

	/**
	 * Read timeout [ms]
	 */
	protected $readTimeout;

	/**
	 * G-S interaction class
	 */
	protected $gsInteractionClass;

	/**
	 * P-R interaction class
	 */
	protected $prInteractionClass;

	/**
	 * Value formatter
	 */
	protected $formatter;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param $options options map
	 */
	public function __construct(&$crypto, $options = array()) {
		$this->crypto = $crypto;
		$this->url = 'http://root:@localhost:9000';
		$this->customer = 'default';
		$this->index = 'default';
		$this->language = 'en';
		$this->dialog = 'standard';
		$this->stateHandler = NULL;
		$this->requestHandler = NULL;
		$this->responseHandler = NULL;
		$this->connectionTimeout = 10000;
		$this->readTimeout = 15000;
		$this->gsInteractionClass = 'CEM_GS_Interaction';
		$this->prInteractionClass = 'CEM_PR_Interaction';
		$this->formatter = new CEM_WebFormatter(Locale::getDefault(), NULL, NULL);
		foreach ($options as $key => $value) {
			$this->$key = $value;
		}
		$this->lastInteraction = NULL;
	}


	/**
	 * Get customer account identifier
	 *
	 * @return customer account identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}
	
	/**
	 * Get index identifier
	 *
	 * @return index identifier
	 */
	public function getIndex() {
		return $this->index;
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
	 * Set language identifier
	 *
	 * @param $language language identifier
	 */
	public function setLanguage($language) {
		$this->language = $language;
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
	 * Get state handler
	 *
	 * @return state handler
	 */
	public function getStateHandler() {
		return $this->stateHandler;
	}
	
	/**
	 * Get request handler
	 *
	 * @return request handler
	 */
	public function getRequestHandler() {
		return $this->requestHandler;
	}
	
	/**
	 * Get response handler
	 *
	 * @return response handler
	 */
	public function getResponseHandler() {
		return $this->responseHandler;
	}


	/**
	 * Get current client state
	 *
	 * @return client state or NULL if none
	 */
	public function currentState() {
		if ($this->stateHandler) {
			return $this->stateHandler->read();
		}
		return NULL;
	}


	/**
	 * Destroy client state gracefully if any
	 *
	 */
	public function destroy() {
		// get cem state
		list($state, $created) = $this->getState();

		if ($created) {
			return;
		}
		
		// process interaction
		$request = new CEM_GS_GatewayRequest($this->customer, $this->dialog, $this->language);
		$request->appendFreeRequest();
		$response = new CEM_GS_GatewayResponse();

		$this->gs($request, $response);
		$this->lastInteraction = NULL;

		// clear client state
		if ($this->stateHandler) {
			$this->stateHandler->remove($state);
		}
	}

	/**
	 * Process client interaction
	 *
	 * @param $options interaction options passed to handlers
	 * @param $useCache optional parameter to use cache (defaults to TRUE)
	 * @return wrapped cem response
	 */
	public function interact($options = array(), $useCache = TRUE) {
		// return cached interaction if any
		if ($useCache && $this->lastInteraction !== NULL) {
			return $this->lastInteraction;
		}

		// prepare interaction
		list($state, $created) = $this->getState();

		$request = new CEM_GS_GatewayRequest($this->customer, $this->dialog, $this->language);
		if ($created) {
			if ($this->requestHandler) {
				if (!$this->requestHandler->onInit($state, $request)) {
					return;
				}
			} else {
				$request->appendInitRequest();
			}
		}
		$response = new CEM_GS_GatewayResponse();

		// process interaction
		$this->gs($request, $response, $options);

		$this->lastInteraction = new $this->gsInteractionClass($this->crypto, $request, $response, $options, $this->formatter);
		return $this->lastInteraction;
	}

	/**
	 * Get last interaction if any
	 *
	 * @return last interaction or NULL if none
	 */
	public function lastInteraction() {
		return $this->lastInteraction;
	}


	/**
	 * Do query completion suggestion
	 *
	 * @param $query query prefix to complete
	 * @param $size suggested query count (defaults to 10)
	 * @param $contextual recommended item count (defaults to 3)
	 * @param $options recommendation options
	 * @return wrapped cem response or FALSE on error
	 */
	public function suggest($query, $size = 10, $contextual = 3, $options = array()) {
		// prepare interaction
		$request = new CEM_PR_GatewayRequest($this->customer);
		$request->addRequest(
			new CEM_PR_CompletionQuery(
				$this->index,
				$this->language,
				isset($options['filter']) ? $options['filter'] : '@type:instance',
				$query,
				$size,
				$contextual,
				isset($options['includedProperties']) ? $options['includedProperties'] : array(),
				isset($options['excludedProperties']) ? $options['excludedProperties'] : array('title'),
				isset($options['filterProperties']) ? $options['filterProperties'] : array('title', 'body'),
				isset($options['scorerProperties']) ? $options['scorerProperties'] : array('title', 'body'),
				isset($options['disambiguationPriorities']) ? $options['disambiguationPriorities'] : array()
			)
		);
		$response = new CEM_PR_GatewayResponse();

		// process interaction
		$this->pr($request, $response, $options);

		return new $this->prInteractionClass($this->crypto, $request, $response, $options, $this->formatter);
	}

	/**
	 * Do refinements preview
	 *
	 * @param $refinements refinements
	 * @param $size suggested products
	 * @param $options recommendation options
	 * @return refinements with previews
	 */
	public function previewRefinements($refinements, $size = 1, $options = array()) {
		// find model
		if ($this->lastInteraction == NULL) {
			return $refinements;
		}
		$cemModel = json_decode($this->lastInteraction->getContextData('model'));

		// prepare interaction
		$request = new CEM_PR_GatewayRequest($this->customer);
		$request->addRequest(
			new CEM_PR_GuidancePreviews(
				array(
					'index' => $this->index,
					'parameters' => array(
						array('name' => 'language', 'value' => $this->language)
					),
					'filter' => isset($options['filter']) ? $options['filter'] : '@type:instance',
					'scorer' => isset($options['scorer']) ? $options['scorer'] : '',
					'ranking' => isset($options['ranking']) ? $options['ranking'] : '@random asc'
				),
				isset($cemModel->queryText) ? $cemModel->queryText : '',
				isset($cemModel->queryTerms) ? $cemModel->queryTerms : array(),
				isset($cemModel->guidances) ? $cemModel->guidances : array(),
				isset($options['filterProperties']) ? $options['filterProperties'] : array('title', 'body'),
				isset($options['scorerProperties']) ? $options['scorerProperties'] : array('title', 'body'),
				$refinements,
				array(),
				$size,
				FALSE
			)
		);
		$response = new CEM_PR_GatewayResponse();

		// process interaction
		$this->pr($request, $response, $options);

		if (!$response->getStatus()) {
			return $refinements;
		}
		$responses = $response->getResponses();
		return $responses[0]->refinements;
	}

	/**
	 * Do attribute preview
	 *
	 * @param $attribute attribute
	 * @param $size suggested products
	 * @param $alternatives alternative flag
	 * @param $options recommendation options
	 * @return attribute with previews
	 */
	public function previewAttribute($attribute, $size = 1, $alternatives = FALSE, $options = array()) {
		// find model
		if ($this->lastInteraction == NULL) {
			return $attribute;
		}
		$cemModel = json_decode($this->lastInteraction->getContextData('model'));

		// prepare interaction
		$request = new CEM_PR_GatewayRequest($this->customer);
		$request->addRequest(
			new CEM_PR_GuidancePreviews(
				array(
					'index' => $this->index,
					'parameters' => array(
						array('name' => 'language', 'value' => $this->language)
					),
					'filter' => isset($options['filter']) ? $options['filter'] : '@type:instance',
					'scorer' => isset($options['scorer']) ? $options['scorer'] : '',
					'ranking' => isset($options['ranking']) ? $options['ranking'] : '@random asc'
				),
				isset($cemModel->queryText) ? $cemModel->queryText : '',
				isset($cemModel->queryTerms) ? $cemModel->queryTerms : array(),
				isset($cemModel->guidances) ? $cemModel->guidances : array(),
				isset($options['filterProperties']) ? $options['filterProperties'] : array('title', 'body'),
				isset($options['scorerProperties']) ? $options['scorerProperties'] : array('title', 'body'),
				array(),
				array($attribute),
				$size,
				$alternatives
			)
		);
		$response = new CEM_PR_GatewayResponse();

		// process interaction
		$this->pr($request, $response, $options);

		if (!$response->getStatus()) {
			return $attribute;
		}
		$responses = $response->getResponses();
		return $responses[0]->attributes[0];
	}


	/**
	 * Do GS request (low-level)
	 *
	 * @param &$request guided-search request
	 * @param &$response guided-search response
	 * @param $options interaction options passed to handlers
	 */
	public function gs(&$request, &$response, $options = array()) {
		// get cem state
		list($state, $created) = $this->getState();

		// process interaction
		$client = new CEM_GatewayClient($this->url . '/gs/gateway/client-1.4', $this->connectionTimeout, $this->readTimeout);
		if ($this->requestHandler && !$this->requestHandler->onInteraction($state, $request, $options)) {
			return;
		}
		if ($client->process($state, $request, $response)) {
			if ($this->responseHandler) {
				$this->responseHandler->onInteraction($state, $request, $response, $options);
			}
		} else {
			if ($this->responseHandler) {
				$this->responseHandler->onError($state, $request, $options);
			}
		}

		// write client state
		if ($this->stateHandler) {
			$this->stateHandler->write($state);
		}
	}

	/**
	 * Do PR request (low-level)
	 *
	 * @param &$request recommendation request
	 * @param &$response recommendation response
	 * @param $options recommendation options
	 */
	public function pr(&$request, &$response, $options = array()) {
		// build cem state
		list($state, $created) = $this->getState();

		// process recommendation
		$client = new CEM_GatewayClient($this->url . '/pr/gateway/client-1.4', $this->connectionTimeout, $this->readTimeout);
		if ($this->requestHandler && !$this->requestHandler->onRecommendation($state, $request, $options)) {
			return;
		}
		if ($client->process($state, $request, $response)) {
			if ($this->responseHandler) {
				$this->responseHandler->onRecommendation($state, $request, $response, $options);
			}
		} else {
			if ($this->responseHandler) {
				$this->responseHandler->onError($state, $request, $options);
			}
		}
	}


	/**
	 * Get client state or create one if necessary
	 *
	 * @return list(client state, created flag)
	 */
	protected function getState() {
		if ($this->stateHandler) {
			$state = $this->stateHandler->read();
			if ($state != NULL) {
				return array($state, FALSE);
			}

			$state = $this->stateHandler->create();
			if ($state != NULL) {
				return array($state, TRUE);
			}
		}
		return array(new CEM_GatewayState(), TRUE);
	}
}

/**
 * @}
 */

?>