<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2011 - Boxalino AG
 */


/**
 * Cookie-based CEM state handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebStateCookieHandler14 extends CEM_WebStateHandler {
	/**
	 * Cookie prefix
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Domain
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * Secure flag
	 *
	 * @var boolean
	 */
	protected $secure;

	/**
	 * Visitor expiry time
	 *
	 * @var integer
	 */
	protected $expiry;

	/**
	 * Chunk length
	 *
	 * @var integer
	 */
	protected $chunkLength;
	

	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param string $prefix cookie prefix (defaults to 'cem')
	 * @param string $path path (defaults to '/')
	 * @param string $domain domain (defaults to any)
	 * @param boolean $secure secure flag (defaults to FALSE)
	 * @param integer $expiry visitor expiry time in seconds (defaults to 30 days)
	 * @param integer $chunkLength cookie chunk size (defaults to 4095 bytes)
	 */
	public function __construct(&$crypto, $prefix = 'cem', $path = '/', $domain = FALSE, $secure = FALSE, $expiry = 2592000, $chunkLength = 4095) {
		parent::__construct($crypto);
		$this->prefix = $prefix;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->expiry = $expiry;
		$this->chunkLength = $chunkLength;

		// parse cem, levels, state data cookies
		$cem = $this->readCookies($this->prefix.'a');
		$visitor = $this->readCookies($this->prefix.'b');
		$session = $this->readCookies($this->prefix.'c');
		$search = $this->readCookies($this->prefix.'d');
		$data = $this->readCookies($this->prefix.'e');
		if ($cem != null || $visitor != NULL || $session != NULL || $search != NULL || $data != NULL) {
			$this->state = new CEM_GatewayState();

			// decode cem client cookies
			if (strlen($cem) > 0) {
				foreach (explode(';', $cem) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$this->state->setCookie(urldecode($name), urldecode($value));
					}
				}
			}

			// decode context levels
			$context = array();
			if (strlen($visitor) > 0) {
				foreach (explode(';', $visitor) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[$this->unescapeValue($name)] = array(
							'level' => 'visitor',
							'mode' => 'aggregate',
							'data' => $this->unescapeValue($value)
						);
					}
				}
			}
			if (strlen($session) > 0) {
				foreach (explode(';', $session) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[$this->unescapeValue($name)] = array(
							'level' => 'session',
							'mode' => 'aggregate',
							'data' => $this->unescapeValue($value)
						);
					}
				}
			}
			if (strlen($search) > 0) {
				foreach (explode(';', $search) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[$this->unescapeValue($name)] = array(
							'level' => 'search',
							'mode' => 'aggregate',
							'data' => $this->unescapeValue($value)
						);
					}
				}
			}
			$this->state->set('context', $context);

			// decode other state data
			if (strlen($data) > 0) {
				foreach (explode(';', $data) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$this->state->set(
							$this->unescapeValue($name),
							json_decode($this->unescapeValue($value))
						);
					}
				}
			}
		}
	}


	/**
	 * Write client state to storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function write(&$state) {
		// write cem cookies
		$this->writeCookies($this->prefix.'a', $state->getCookieHeader(), FALSE);

		// write levels cookies
		$context = $state->get('context', array());
		$visitor = '';
		$session = '';
		$search = '';
		foreach ($context as $key => $item) {
			if ($item['mode'] == 'aggregate') {
				switch ($item['level']) {
				case 'visitor':
					if (strlen($visitor) > 0) {
						$visitor .= ';';
					}
					$visitor .= $this->escapeValue($key) . '=' . $this->escapeValue($item['data']);
					break;

				case 'session':
					if (strlen($session) > 0) {
						$session .= ';';
					}
					$session .= $this->escapeValue($key) . '=' . $this->escapeValue($item['data']);
					break;

				case 'search':
					if (strlen($search) > 0) {
						$search .= ';';
					}
					$search .= $this->escapeValue($key) . '=' . $this->escapeValue($item['data']);
					break;
				}
			}
		}
		$this->writeCookies($this->prefix.'b', $visitor, TRUE);
		$this->writeCookies($this->prefix.'c', $session, FALSE);
		$this->writeCookies($this->prefix.'d', $search, FALSE);

		// write state data cookies
		$data = '';
		foreach ($state->getAll() as $key => $value) {
			if ($key != 'context') {
				if (strlen($data) > 0) {
					$data .= ';';
				}
				$data .= $this->escapeValue($key) . '=' . $this->escapeValue(json_encode($value));
			}
		}
		$this->writeCookies($this->prefix.'e', $data, FALSE);

		parent::write($state);
	}

	/**
	 * Remove client state from storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function remove(&$state) {
		// clear cem, levels, state data cookies
		writeCookies($this->prefix.'a');
		writeCookies($this->prefix.'b');
		writeCookies($this->prefix.'c');
		writeCookies($this->prefix.'d');
		writeCookies($this->prefix.'e');

		parent::remove($state);
	}


	/**
	 * Read cookie sequence
	 *
	 * @param string $prefix cookie prefix
	 * @return string plain cookie data
	 */
	protected function readCookies($prefix) {
		$i = 0;
		$data = '';
		while (isset($_COOKIE[$prefix.$i])) {
			$data .= $_COOKIE[$prefix.$i];
			$i++;
		}
		return $this->decrypt($data);
	}

	/**
	 * Write cookie sequence
	 *
	 * @param string $prefix cookie prefix
	 * @param string $data plain cookie data
	 * @param boolean $visitor visitor if true, session if false
	 */
	protected function writeCookies($prefix, $data = '', $visitor = FALSE) {
		$i = 0;
		$data = $this->encrypt($data);
		if ($data) {
			$offset = 0;
			while ($offset < strlen($data)) {
				if (($offset + $this->chunkLength) < strlen($data)) {
					$chunk = substr($data, $offset, $this->chunkLength);
				} else {
					$chunk = substr($data, $offset);
				}
				setcookie(
					$prefix.$i,
					$chunk,
					$visitor ? time() + $this->expiry : 0,
					$this->path,
					$this->domain,
					$this->secure
				);
				$offset += $this->chunkLength;
				$i++;
			}
		}
		while (isset($_COOKIE[$prefix.$i]) && strlen($_COOKIE[$prefix.$i]) > 0) {
			setcookie($prefix.$i, '', time() - (24 * 60 * 60), $this->path, $this->domain, $this->secure);
			$i++;
		}
	}
}


/**
 * Default CEM request handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebRequestHandler14 extends CEM_AbstractWebHandler {
	/**
	 * Request variables
	 *
	 * @var array
	 */
	protected $context;

	/**
	 * Sequential contexts
	 *
	 * @var array
	 */
	protected $sequentialContexts;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $keys request parameter mapping
	 */
	public function __construct(&$crypto, $keys = array()) {
		parent::__construct($crypto, $keys);
		$this->sequentialContexts = array();

		// decode sequential context states
		if ($this->requestExists('context')) {
			$data = $this->decrypt($this->requestString('context'));
			if ($data) {
				foreach (explode(';', $data) as $scope) {
					list($name, $level, $data) = explode('=', $scope);

					$name = $this->unescapeValue($name);
					$level = $this->unescapeValue($level);
					$data = $this->unescapeValue($data);
					$this->sequentialContexts[$name] = array(
						'level' => $level,
						'mode' => 'sequential',
						'data' => $data
					);
				}
			}
		}
	}


	/**
	 * Get request variables
	 *
	 * @return array request variables
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Set request variables
	 *
	 * @param array $context request variables
	 */
	public function setContext($context) {
		foreach ($context as $key => $value) {
			$this->context[$key] = $value;
		}
	}


	/**
	 * Get sequential contexts
	 *
	 * @return array sequential contexts
	 */
	public function getSequentialContexts() {
		return $this->sequentialContexts;
	}

	/**
	 * Get sequential context variables
	 *
	 * @param string $name context name
	 * @return array context variables
	 */
	public function getSequentialContext($name) {
		if (isset($this->sequentialContexts[$name])) {
			return $this->sequentialContexts[$name]['data'];
		}
		return '';
	}


	/**
	 * Called when client state needs to be initialized
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onInit(&$state, &$request) {
		$request->appendInitRequest();
		return TRUE;
	}

	/**
	 * Called when client state needs to be freed
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onFree(&$state, &$request) {
		$request->appendFreeRequest();
		return TRUE;
	}

	/**
	 * Called each client interaction to build request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @param array &$options options passed for interaction
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onInteraction(&$state, &$request, &$options) {
		$variables = $this->buildInteractionVariables($options);

		// merge contexts
		$contexts = $state->get('context', array());
		foreach ($this->sequentialContexts as $name => $value) {
			$contexts[$name] = $value;
		}
		$state->set('context', $contexts);

		// get cem model context
		$model = isset($contexts['model']) ? json_decode($contexts['model']['data']) : array();

		// notify custom implementation
		$action = $this->onInteractionBefore($state, $request, 'none', $variables, $options);

		// base parameters
		if ($this->requestExists('offset')) {
			$variables['offset'] = $this->requestNumber('offset');
		}
		if ($this->requestExists('pageSize')) {
			$variables['pageSize'] = $this->requestNumber('pageSize');
		} else if (isset($model->pageSize)) {
			$variables['pageSize'] = $model->pageSize;
		}

		// base context
		$query = array();
		if (isset($this->context['query'])) {
			$query = $this->context['query'];
		}
		if ($this->requestExists('filter')) {
			$query['filter'] = $this->requestString('filter');
		}
		if ($this->requestExists('scorer')) {
			$query['scorer'] = $this->requestString('scorer');
		}
		if ($this->requestExists('snippet')) {
			$query['snippet'] = $this->requestString('snippet');
		}
		if ($this->requestExists('ranking')) {
			$query['ranking'] = $this->requestString('ranking');
		} else if (isset($model->ranking)) {
			$query['ranking'] = $model->ranking;
		}
		if (sizeof($query) > 0) {
			$variables['query'] = $query;
		}

		// controller logic
		if ($this->requestExists('query')) {
			$action = 'query';
			$variables['queryText'] = $this->requestString('query');
			if ($this->requestExists('ac')) {
				$variables['ac'] = $this->requestNumber('ac');
			}
		} else if ($this->requestExists('refine')) {
			if ($this->requestExists('clear')) {
				$action = 'clearQuery';
				$variables['keepTerms'] = TRUE;
			} else {
				$action = 'refine';
				$variables['refine'] = $this->requestNumber('refine');
				$variables['property'] = $this->requestString('property');
				$variables['value'] = $this->requestString('value');
			}
		} else if ($this->requestExists('guidance')) {
			$guidance = $this->requestString('guidance');
			if (is_numeric($guidance)) {
				$action = 'delGuidance';
				$variables['guidance'] = $this->requestNumber('guidance');
				$variables['property'] = '';
			} else if (strpos($guidance, '-') === 0) {
				$action = 'delGuidance';
				$variables['guidance'] = -1;
				$variables['property'] = substr($guidance, 1);
			} else {
				if (strpos($guidance, '+') === 0 || strpos($guidance, ' ') === 0) {
					$action = 'addGuidance';
					$variables['type'] = substr($guidance, 1);
				} else {
					$action = 'setGuidance';
					$variables['type'] = $guidance;
				}
				if ($this->requestNumber('hierarchical') > 0) {
					$variables['mode'] = 'hierarchical';
				} else if ($this->requestExists('mode')) {
					$variables['mode'] = $this->requestString('mode');
				} else {
					$variables['mode'] = 'guidance';
				}
				$variables['property'] = $this->requestString('property');
				if ($variables['mode'] == 'hierarchical') {
					$variables['value'] = array();
					for ($i = 0; $i < $this->requestNumber('hierarchical'); $i++) {
						$value = $this->requestStringArray('value'.$i);
						$variables['value'][] = $value[0];
					}
				} else {
					$variables['value'] = $this->requestStringArray('value');
				}
			}
		} else if ($this->requestExists('feedback')) {
			$action = 'feedback';
			$variables['weight'] = $this->requestNumber('feedback');
		}

		// custom overrides
		if (isset($options['action'])) {
			$action = strval($options['action']);
		}
		if (isset($options['variables'])) {
			foreach ($options['variables'] as $key => $value) {
				$variables[$key] = $value;
			}
		}

		// notify custom implementation
		$action = $this->onInteractionAfter($state, $request, $action, $variables, $options);

		// add final request
		$request->appendRequest($action, $variables);
		return TRUE;
	}

	/**
	 * Build interaction variables
	 *
	 * @param array &$options options passed for interaction
	 * @return array contextual request variables
	 */
	protected function buildInteractionVariables(&$options) {
		$variables = array();
		foreach ($this->context as $key => $value) {
			$variables[$key] = $value;
		}
		return $variables;
	}

	/**
	 * Called before default interaction request is built
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @param string $action current action identifier
	 * @param array &$variables contextual request variables
	 * @param array &$options options passed for interaction
	 * @return string final action identifier
	 */
	protected function onInteractionBefore(&$state, &$request, $action, &$variables, &$options) {
		return $action;
	}

	/**
	 * Called after default interaction request is built
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @param string $action current action identifier
	 * @param array &$variables contextual request variables
	 * @param array &$options options passed for interaction
	 * @return string final action identifier
	 */
	protected function onInteractionAfter(&$state, &$request, $action, &$variables, &$options) {
		return $action;
	}


	/**
	 * Called each client recommendation to build request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_GatewayRequest14 &$request client request reference
	 * @param array &$options options passed for recommendation
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onRecommendation(&$state, &$request, $options) {
		return TRUE;
	}
}


/**
 * Default CEM response handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebResponseHandler14 extends CEM_AbstractWebHandler {
	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 */
	public function __construct(&$crypto) {
		parent::__construct($crypto);
	}


	/**
	 * Called each client interaction to wrap the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @param CEM_GS_GatewayResponse14 &$response client response reference
	 * @param array &$options options passed for interaction
	 */
	public function onInteraction(&$state, &$request, &$response, &$options) {
	}

	/**
	 * Called each client recommendation to wrap the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_GatewayRequest14 &$request client request reference
	 * @param CEM_PR_GatewayResponse14 &$response client response reference
	 * @param array &$options options passed for recommendation
	 */
	public function onRecommendation(&$state, &$request, &$response, &$options) {
	}

	/**
	 * Called if client interaction triggers an error
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_GatewayRequest14|CEM_GS_GatewayRequest14 &$request client request reference
	 * @param array &$options options passed for recommendation
	 */
	public function onError(&$state, &$request, &$options) {
	}
}


/**
 * PR interaction for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_PR_Interaction14 extends CEM_AbstractWebHandler {
	/**
	 * Current request
	 *
	 * @var CEM_PR_GatewayRequest14
	 */
	protected $request;

	/**
	 * Current response
	 *
	 * @var CEM_PR_GatewayResponse14
	 */
	protected $response;

	/**
	 * User-defined options
	 *
	 * @var array
	 */
	protected $options;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param CEM_PR_GatewayRequest14 &$request client request reference
	 * @param CEM_PR_GatewayResponse14 &$response client response reference
	 * @param array &$options user-defined options
	 */
	public function __construct(&$crypto, &$request, &$response, &$options) {
		parent::__construct($crypto);
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
	}


	/**
	 * Get underlying request
	 *
	 * @return CEM_PR_GatewayRequest14 underlying request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Get underlying response
	 *
	 * @return CEM_PR_GatewayResponse14 underlying response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get user-defined options
	 *
	 * @return array user-defined options
	 */
	public function getOptions() {
		return $this->options;
	}


	/**
	 * Get recommendations
	 *
	 * @return array recommendations
	 */
	public function getRecommendations() {
		return $this->response->getResponses();
	}
}


/**
 * GS interaction for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_GS_Interaction14 extends CEM_AbstractWebHandler {
	/**
	 * Current request
	 *
	 * @var CEM_GS_GatewayRequest14
	 */
	protected $request;

	/**
	 * Current response
	 *
	 * @var CEM_GS_GatewayResponse14
	 */
	protected $response;

	/**
	 * User-defined options
	 *
	 * @var array
	 */
	protected $options;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param CEM_GS_GatewayRequest14 &$request client request reference
	 * @param CEM_GS_GatewayResponse14 &$response client response reference
	 * @param array &$options user-defined options
	 */
	public function __construct(&$crypto, &$request, &$response, &$options) {
		parent::__construct($crypto);
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
	}


	/**
	 * Get underlying request
	 *
	 * @return CEM_GS_GatewayRequest14 underlying request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Get underlying response
	 *
	 * @return CEM_GS_GatewayResponse14 underlying response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get user-defined options
	 *
	 * @return array user-defined options
	 */
	public function getOptions() {
		return $this->options;
	}


	/**
	 * Get context scopes
	 *
	 * @return array context scopes
	 */
	public function getContext() {
		return $this->response->getContext();
	}

	/**
	 * Get context data
	 *
	 * @param string $name context name
	 * @return string context data
	 */
	public function getContextData($name) {
		$scopes = $this->getContext();
		if (isset($scopes[$name])) {
			return $scopes[$name]['data'];
		}
		return '';
	}


	/**
	 * Encode sequential contexts
	 *
	 * @return string encoded sequential contexts
	 */
	public function encodeSequentialContexts() {
		$data = '';
		foreach ($this->response->getContext() as $name => $scope) {
			if ($scope['mode'] == 'sequential') {
				switch ($scope['level']) {
				case 'visitor':
				case 'session':
				case 'search':
					if (strlen($data) > 0) {
						$data .= ';';
					}
					$data .= $this->escapeValue($name) . '=' . $this->escapeValue($scope['level']) . '=' . $this->escapeValue($scope['data']);
					break;
				}
			}
		}
		return $this->encrypt($data);
	}

	/**
	 * Encode parameters and state into url
	 *
	 * @param array $parameters additional query parameters
	 * @return string encoded url query
	 */
	public function encodeQuery($parameters = array()) {
		$context = $this->encodeSequentialContexts();
		if ($context) {
			$parameters['context'] = $context;
		}
		$query = '';
		foreach ($parameters as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $item) {
					if (strlen($query) > 0) {
						$query .= '&';
					}
					$query .= urlencode($this->requestKey($key)).'[]='.urlencode($item);
				}
			} else {
				if (strlen($query) > 0) {
					$query .= '&';
				}
				$query .= urlencode($this->requestKey($key)).'='.urlencode($value);
			}
		}
		if (strlen($query) > 0) {
			return ('?'.$query);
		}
		return '';
	}


	/**
	 * Get groups
	 *
	 * @return array group scopes
	 */
	public function getGroups() {
		return $this->response->getResponses();
	}

	/**
	 * Get group
	 *
	 * @param string $id group identifier
	 * @return object group or NULL if none
	 */
	public function getGroup($id = 'main') {
		$scopes = $this->response->getResponses();
		if (isset($scopes[$id])) {
			return $scopes[$id];
		}
		return NULL;
	}
}


/**
 * CEM controller for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebController14 {
	/**
	 * Encryption facility
	 *
	 * @var CEM_WebEncryption
	 */
	protected $crypto;

	/**
	 * CEM server url (defaults to 'http://root:@localhost:9000')
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Customer account (defaults to 'default')
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Index (defaults to 'default')
	 *
	 * @var string
	 */
	protected $index;

	/**
	 * Language (defaults to 'en')
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Dialog (defaults to 'search')
	 *
	 * @var string
	 */
	protected $dialog;

	/**
	 * State handler
	 *
	 * @var CEM_WebStateHandler
	 */
	protected $stateHandler;

	/**
	 * Request handler
	 *
	 * @var CEM_WebRequestHandler14
	 */
	protected $requestHandler;

	/**
	 * Response handler
	 *
	 * @var CEM_WebResponseHandler14
	 */
	protected $responseHandler;

	/**
	 * Connection timeout [ms]
	 *
	 * @var integer
	 */
	protected $connectionTimeout;

	/**
	 * Read timeout [ms]
	 *
	 * @var integer
	 */
	protected $readTimeout;

	/**
	 * Last interaction response
	 *
	 * @var CEM_GS_Interaction14
	 */
	protected $lastInteraction;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $options options map
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
		foreach ($options as $key => $value) {
			$this->$key = $value;
		}
		$this->lastInteraction = NULL;
	}

	
	/**
	 * Get customer account identifier
	 *
	 * @return string customer account identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}
	
	/**
	 * Get index identifier
	 *
	 * @return string index identifier
	 */
	public function getIndex() {
		return $this->index;
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
	 * Set language identifier
	 *
	 * @param string $language language identifier
	 */
	public function setLanguage($language) {
		$this->language = $language;
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
	 * Get state handler
	 *
	 * @return CEM_WebStateHandler state handler
	 */
	public function getStateHandler() {
		return $this->stateHandler;
	}
	
	/**
	 * Get request handler
	 *
	 * @return CEM_WebRequestHandler14 request handler
	 */
	public function getRequestHandler() {
		return $this->requestHandler;
	}
	
	/**
	 * Get response handler
	 *
	 * @return CEM_WebResponseHandler14 response handler
	 */
	public function getResponseHandler() {
		return $this->responseHandler;
	}


	/**
	 * Get current client state
	 *
	 * @return CEM_GatewayState client state or NULL if none
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
		$request = new CEM_GS_GatewayRequest14($this->customer, $this->dialog, $this->language);
		$request->appendFreeRequest();
		$response = new CEM_GS_GatewayResponse14();

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
	 * @param array $options interaction options passed to handlers
	 * @param boolean $useCache optional parameter to use cache (defaults to TRUE)
	 * @return CEM_GS_Interaction14 wrapped cem response
	 */
	public function interact($options = array(), $useCache = TRUE) {
		// return cached interaction if any
		if ($useCache && $this->lastInteraction !== NULL) {
			return $this->lastInteraction;
		}

		// prepare interaction
		list($state, $created) = $this->getState();

		$request = new CEM_GS_GatewayRequest14($this->customer, $this->dialog, $this->language);
		if ($created) {
			if ($this->requestHandler) {
				if (!$this->requestHandler->onInit($state, $request)) {
					return;
				}
			} else {
				$request->appendInitRequest();
			}
		}
		$response = new CEM_GS_GatewayResponse14();

		// process interaction
		$this->gs($request, $response, $options);

		$this->lastInteraction = new CEM_GS_Interaction14($this->crypto, $request, $response, $options);
		return $this->lastInteraction;
	}


	/**
	 * Do query completion suggestion
	 *
	 * @param string $query query prefix to complete
	 * @param integer $size suggested query count (defaults to 10)
	 * @param integer $contextual recommended item count (defaults to 3)
	 * @param array $options recommendation options
	 * @return mixed wrapped cem response or FALSE on error
	 */
	public function suggest($query, $size = 10, $contextual = 3, $options = array()) {
		// prepare interaction
		$request = new CEM_PR_GatewayRequest14($this->customer);
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
				isset($options['scorerProperties']) ? $options['scorerProperties'] : array('title', 'body')
			)
		);
		$response = new CEM_PR_GatewayResponse14();

		// process interaction
		$this->pr($request, $response, $options);

		return new CEM_PR_Interaction14($this->crypto, $request, $response, $options);
	}


	/**
	 * Do GS request (low-level)
	 *
	 * @param CEM_GS_GatewayRequest14 &$request guided-search request
	 * @param CEM_GS_GatewayResponse14 &$response guided-search response
	 * @param array $options interaction options passed to handlers
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
	 * @param CEM_PR_GatewayRequest14 &$request recommendation request
	 * @param CEM_PR_GatewayResponse14 &$response recommendation response
	 * @param array $options recommendation options
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
	 * @return array list(client state, created flag)
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

?>