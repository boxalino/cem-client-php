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
 * CEM controller for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebController {
	/**
	 * State handler
	 */
	protected $stateHandler;

	/**
	 * Action encoder
	 */
	protected $encoder;

	/**
	 * Value formatter
	 */
	protected $formatter;

	/**
	 * Gateway client
	 */
	protected $client;

	/**
	 * CEM server url (gs)
	 */
	protected $gsUrl = NULL;

	/**
	 * G-S interaction class
	 */
	protected $gsInteractionClass = 'CEM_GS_Interaction';

	/**
	 * CEM server url (pr)
	 */
	protected $prUrl = NULL;

	/**
	 * P-R interaction class
	 */
	protected $prInteractionClass = 'CEM_PR_Interaction';

	/**
	 * Customer account (defaults to 'default')
	 */
	protected $customer = 'default';

	/**
	 * Index (defaults to 'default')
	 */
	protected $index = 'default';

	/**
	 * Language (defaults to 'en')
	 */
	protected $language = 'en';

	/**
	 * Dialog (defaults to 'standard')
	 */
	protected $dialog = 'standard';

	/**
	 * Last interaction
	 */
	protected $lastInteraction = NULL;


	/**
	 * Constructor
	 *
	 * @param $stateHandler state handler
	 * @param $encoder action encoder
	 * @param $formatter value formatter
	 * @param $connectionTimeout connection timeout in milliseconds
	 * @param $readTimeout read timeout in milliseconds
	 * @param $options options map
	 */
	public function __construct($stateHandler, $encoder, $formatter, $connectionTimeout = 5000, $readTimeout = 15000, $options = array()) {
		$this->stateHandler = $stateHandler;
		$this->encoder = $encoder;
		$this->formatter = $formatter;
		$this->client = new CEM_GatewayClient($connectionTimeout, $readTimeout);
		if (isset($options['url']) && strlen($options['url']) > 0) {
			$this->gsUrl = $options['url'].'/gs/gateway/client-1.4';
			$this->prUrl = $options['url'].'/pr/gateway/client-1.4';
		} else if (isset($options['routerUrl']) && strlen($options['routerUrl']) > 0) {
			$this->gsUrl = $options['routerUrl'].'/cem/client/gs';
			$this->prUrl = $options['routerUrl'].'/cem/client/pr';
		}
		foreach ($options as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
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
	 * Get encoder
	 *
	 * @return encoder
	 */
	public function getEncoder() {
		return $this->encoder;
	}

	/**
	 * Get formatter
	 *
	 * @return formatter
	 */
	public function getFormatter() {
		return $this->formatter;
	}


	/**
	 * Get service url (gs)
	 *
	 * @return service url
	 */
	public function getGsUrl() {
		return $this->gsUrl;
	}

	/**
	 * Set service url (gs)
	 *
	 * @param $url service url
	 */
	public function setGsUrl($url) {
		$this->gsUrl = $url;
	}

	/**
	 * Get service url (pr)
	 *
	 * @return service url
	 */
	public function getPrUrl() {
		return $this->prUrl;
	}

	/**
	 * Set service url (pr)
	 *
	 * @param $url service url
	 */
	public function setPrUrl($url) {
		$this->prUrl = $url;
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
	 * Set customer identifier
	 *
	 * @param $customer customer identifier
	 */
	public function setCustomer($customer) {
		$this->customer = $customer;
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
	 * Set index identifier
	 *
	 * @param $index index identifier
	 */
	public function setIndex($index) {
		$this->index = $index;
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
	 * Set dialog identifier
	 *
	 * @param $dialog dialog identifier
	 */
	public function setDialog($dialog) {
		$this->dialog = $dialog;
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
			$request->appendInitRequest();
		}
		$response = new CEM_GS_GatewayResponse();

		// process interaction
		$this->gs($request, $response, $options, $useCache);

		// wrap response
		$interaction = new $this->gsInteractionClass($this->encoder, $this->formatter, $request, $response, $options);
		if ($useCache) {
			$this->lastInteraction = $interaction;
		}
		return $interaction;
	}

	/**
	 * Process client interaction (detail)
	 *
	 * @param $sourceIds source identifiers
	 * @param $options interaction options passed to handlers
	 * @param $useCache optional parameter to use cache (defaults to TRUE)
	 * @return wrapped cem response
	 */
	public function interactDetail($sourceIds = array(), $options = array(), $useCache = TRUE) {
		// build request batch
		$ids = array();
		foreach ($sourceIds as $sourceId) {
			$ids[] = '"'.addcslashes($sourceId, '"').'"';
		}

		$batch = isset($options['batch']) ? $options['batch'] : array();
		$batch[] = array(
			'action' => 'detail',
			'variables' => array(
				'sourceFilter' => '@type:instance&@id:('.implode(',', $ids).')'
			)
		);
		$options['batch'] = $batch;
		return $this->interact($options, $useCache);
	}

	/**
	 * Get last interaction if any
	 *
	 * @param $lastInteraction optional last interaction to set
	 * @return last interaction or NULL if none
	 */
	public function lastInteraction($lastInteraction = NULL) {
		if ($lastInteraction) {
			$this->lastInteraction = $lastInteraction;
		}
		return $this->lastInteraction;
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

		// prepare interaction
		$request = new CEM_GS_GatewayRequest($this->customer, $this->dialog, $this->language);
		$request->appendFreeRequest();
		$response = new CEM_GS_GatewayResponse();

		// process interaction
		$this->gs($request, $response);

		// clear client state
		$this->stateHandler->remove($state);

		$this->lastInteraction = NULL;
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
				isset($options['parserProperties']) ? $options['parserProperties'] : array('title', 'body'),
				isset($options['includedProperties']) ? $options['includedProperties'] : array(),
				isset($options['excludedProperties']) ? $options['excludedProperties'] : array('title'),
				isset($options['filterProperties']) ? $options['filterProperties'] : array('title', 'body'),
				isset($options['scorerProperties']) ? $options['scorerProperties'] : array('title', 'body'),
				isset($options['disambiguationPriorities']) ? $options['disambiguationPriorities'] : array(),
				isset($options['resultQuery']) ? $options['resultQuery'] : array()
			)
		);
		$response = new CEM_PR_GatewayResponse();

		// process interaction
		$this->pr($request, $response, $options);

		return new $this->prInteractionClass($this->encoder, $this->formatter, $request, $response, $options);
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
					'ranking' => isset($options['ranking']) ? $options['ranking'] : '@score desc, @random asc'
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
					'ranking' => isset($options['ranking']) ? $options['ranking'] : '@score desc, @random asc'
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
	 * @param $request guided-search request
	 * @param $response guided-search response
	 * @param $options interaction options passed to handlers
	 * @param $saveState save state
	 */
	public function gs($request, $response, $options = array(), $saveState = TRUE) {
		if (strlen($this->gsUrl) == 0) {
			throw new Exception("Cannot send request: gsUrl is empty");
		}

		// get cem state
		list($state, $created) = $this->getState();

		$this->encoder->buildStateContexts($state, $options);

		// build request
		$this->encoder->buildInteractionRequest($state, $request, $options);

		// process interaction
		if (!$this->client->exec($this->gsUrl, $state, $request, $response)) {
			throw new Exception("Cannot process request: ".$this->client->getError());
		}

		// build response
		$this->encoder->buildInteractionResponse($state, $response, $options);

		// write client state
		if ($saveState) {
			$this->stateHandler->write($state);
		}
	}

	/**
	 * Do PR request (low-level)
	 *
	 * @param $request recommendation request
	 * @param $response recommendation response
	 * @param $options recommendation options
	 */
	public function pr($request, $response, $options = array()) {
		if (strlen($this->prUrl) == 0) {
			throw new Exception("Cannot send request: prUrl is empty");
		}

		// build cem state
		list($state, $created) = $this->getState();

		// process recommendation
		if (!$this->client->exec($this->prUrl, $state, $request, $response)) {
			throw new Exception("Cannot process request: ".$this->client->getError());
		}
	}


	/**
	 * Get current client state
	 *
	 * @return client state or NULL if none
	 */
	public function currentState() {
		return $this->stateHandler->read();
	}


	/**
	 * Get client state or create one if necessary
	 *
	 * @return list(client state, created flag)
	 */
	protected function getState() {
		$state = $this->stateHandler->read();
		if ($state != NULL) {
			return array($state, FALSE);
		}

		$state = $this->stateHandler->create();
		if ($state != NULL) {
			return array($state, TRUE);
		}
		return array(new CEM_GatewayState(), TRUE);
	}
}

/**
 * @}
 */

?>