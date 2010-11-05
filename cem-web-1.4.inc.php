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
 * Cookie-based CEM state handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebStateCookieHandler14 extends CEM_WebStateHandler {
	/**
	 * Encryption facility
	 *
	 * @var CEM_WebEncryption
	 */
	protected $crypto;

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
	 * @param integer $chunkLength cookie chunk size (defaults to 4096 bytes)
	 */
	public function __construct(&$crypto, $prefix = 'cem', $path = '/', $domain = FALSE, $secure = FALSE, $expiry = 2592000, $chunkLength = 4096) {
		parent::__construct();
		$this->crypto = $crypto;
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

					$name = urldecode($name);
					if (strlen($name) > 0) {
						$this->state->setCookie($name, array('value' => urldecode($value)));
					}
				}
			}

			// decode context levels
			$context = array();
			if (strlen($visitor) > 0) {
				foreach (explode(';', $visitor) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[CEM_WebStateCookieHandler14::unescapeValue($name)] = array(
							'level' => 'visitor',
							'data' => CEM_WebStateCookieHandler14::unescapeValue($value)
						);
					}
				}
			}
			if (strlen($session) > 0) {
				foreach (explode(';', $session) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[CEM_WebStateCookieHandler14::unescapeValue($name)] = array(
							'level' => 'session',
							'data' => CEM_WebStateCookieHandler14::unescapeValue($value)
						);
					}
				}
			}
			if (strlen($search) > 0) {
				foreach (explode(';', $search) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[CEM_WebStateCookieHandler14::unescapeValue($name)] = array(
							'level' => 'search',
							'data' => CEM_WebStateCookieHandler14::unescapeValue($value)
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
							CEM_WebStateCookieHandler14::unescapeValue($name),
							json_decode(CEM_WebStateCookieHandler14::unescapeValue($value))
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
		$data = $state->getCookies();
		if (strlen($data) > 0) {
			$this->writeCookies($this->prefix.'a', $data, FALSE);
		}

		// write levels cookies
		$context = $state->get('context');
		if (is_array($context)) {
			$visitor = '';
			$session = '';
			$search = '';
			foreach ($context as $key => $item) {
				switch ($item['level']) {
				case 'visitor':
					if (strlen($visitor) > 0) {
						$visitor .= ';';
					}
					$visitor .= CEM_WebStateCookieHandler14::escapeValue($key) . '=' . CEM_WebStateCookieHandler14::escapeValue($item['data']);
					break;

				case 'session':
					if (strlen($session) > 0) {
						$session .= ';';
					}
					$session .= CEM_WebStateCookieHandler14::escapeValue($key) . '=' . CEM_WebStateCookieHandler14::escapeValue($item['data']);
					break;

				case 'search':
					if (strlen($search) > 0) {
						$search .= ';';
					}
					$search .= CEM_WebStateCookieHandler14::escapeValue($key) . '=' . CEM_WebStateCookieHandler14::escapeValue($item['data']);
					break;
				}
			}
			if (strlen($visitor) > 0) {
				$this->writeCookies($this->prefix.'b', $visitor, TRUE);
			}
			if (strlen($session) > 0) {
				$this->writeCookies($this->prefix.'c', $session, FALSE);
			}
			if (strlen($search) > 0) {
				$this->writeCookies($this->prefix.'d', $search, FALSE);
			}
		}

		// write state data cookies
		$data = '';
		foreach ($state->getAll() as $key => $value) {
			if ($key != 'context') {
				if (strlen($data) > 0) {
					$data .= ';';
				}
				$data .= CEM_WebStateCookieHandler14::escapeValue($key) . '=' . CEM_WebStateCookieHandler14::escapeValue(json_encode($value));
			}
		}
		if (strlen($data) > 0) {
			$this->writeCookies($this->prefix.'e', $data, FALSE);
		}

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
		if (isset($_COOKIE[$prefix.$i])) {
			$data = $_COOKIE[$prefix.$i];
			$i++;
			while (isset($_COOKIE[$prefix.$i])) {
				$data .= $_COOKIE[$prefix.$i];
				$i++;
			}
			$data = $this->crypto->decrypt(base64_decode($data));
			if ($data && strpos($data, 'cem') === 0) {
				return gzinflate(substr($data, 3));
			}
		}
		return NULL;
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
		if (strlen($data) > 0) {
			$data = $this->crypto->encrypt('cem'.gzdeflate($data, 9));
			if ($data) {
				$offset = 0;
				$data = base64_encode($data);
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
		}
		while (isset($_COOKIE[$prefix.$i]) && strlen($_COOKIE[$prefix.$i]) > 0) {
			setcookie($prefix.$i, '', time() - (24 * 60 * 60), $this->path, $this->domain, $this->secure);
			$i++;
		}
	}

	/**
	 * Escape value ('%' <> '%25', ';' <> '%3B', '=' <> '%3D')
	 *
	 * @param string $value input value
	 * @return escaped value
	 */
	protected static function escapeValue($value) {
		return str_replace(
			array('%', ';', '='),
			array('%25', '%3B', '%3D'),
			$value
		);
	}

	/**
	 * Unescape value ('%' <> '%25', ';' <> '%3B', '=' <> '%3D')
	 *
	 * @param string $value input value
	 * @return escaped value
	 */
	protected static function unescapeValue($value) {
		return str_replace(
			array('%25', '%3B', '%3D'),
			array('%', ';', '='),
			$value
		);
	}
}


/**
 * Abstract CEM handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
abstract class CEM_WebHandler14 extends CEM_AbstractWebHandler {
	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $options context options
	 */
	public function __construct(&$crypto, $options = array()) {
		parent::__construct($crypto, $options);
	}
}


/**
 * Default CEM request handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebRequestHandler14 extends CEM_WebHandler14 {
	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $options context options
	 */
	public function __construct(&$crypto, $options = array()) {
		parent::__construct($crypto, $options);

		// initial context values if given
		if (isset($options['context'])) {
			foreach ($options['context'] as $key => $value) {
				$this->context[$key] = $value;
			}
		}
	}


	/**
	 * Called when client state needs to be initialized
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_SimpleRequest &$request client request reference
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
	 * @param CEM_GS_SimpleRequest &$request client request reference
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
	 * @param CEM_GS_SimpleRequest &$request client request reference
	 * @param array &$options options passed for interaction
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onInteraction(&$state, &$request, &$options) {
		$variables = $this->buildInteractionVariables($options);

		// notify custom implementation
		$jump = $this->onInteractionBefore($state, $request, 'none', $variables, $options);

		// base parameters
		if ($this->requestExists('offset')) {
			$variables['offset'] = $this->requestNumber('offset');
		}
		if ($this->requestExists('pageSize')) {
			$variables['pageSize'] = $this->requestNumber('pageSize');
		}

		// base context
		if ($this->requestExists('filter') || $this->requestExists('scorer') || $this->requestExists('snippet') || $this->requestExists('ranking')) {
			$variables['query'] = array(
				'filter' => $this->requestString('filter'),
				'scorer' => $this->requestString('scorer'),
				'snippet' => $this->requestString('snippet'),
				'ranking' => $this->requestString('ranking')
			);
		}

		// controller logic
		if ($this->requestExists('query')) {
			$jump = 'query';
			$variables['queryText'] = $this->requestString('query');
		} else if ($this->requestExists('refine')) {
			$jump = 'refine';
			$variables['refine'] = $this->requestNumber('refine');
			$variables['property'] = $this->requestString('property');
			$variables['value'] = $this->requestString('value');
		} else if ($this->requestExists('guidance')) {
			if (is_numeric($this->requestString('guidance'))) {
				$jump = 'delGuidance';
				$variables['guidance'] = $this->requestNumber('guidance');
			} else {
				$jump = 'addGuidance';
				$variables['type'] = $this->requestString('guidance');
				$variables['property'] = $this->requestString('property');
				$variables['value'] = $this->requestStringArray('value');
			}
		}

		// custom overrides
		if (isset($options['jump'])) {
			$jump = strval($options['jump']);
		}
		if (isset($options['variables'])) {
			foreach ($options['variables'] as $key => $value) {
				$variables[$key] = $value;
			}
		}
/*		if (isset($options['widgets'])) {
			foreach ($options['widgets'] as $widget) {
				list($template, $instance) = explode('.', $widget);

				if (strlen($template) > 0 && strlen($instance) > 0) {
					$request->addWidget($template, $instance, CEM_GS_WIDGET_DATA);
				}
			}
		}*/

		// notify custom implementation
		$jump = $this->onInteractionAfter($state, $request, $jump, $variables, $options);

		// add final request
		$request->appendRequest($jump, $variables);

		// request state widget
//		$request->addWidget('state', 'default', CEM_GS_WIDGET_DATA);
		return TRUE;
	}

	/**
	 * Called each client recommendation to build request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_AbstractQuery &$request client request reference
	 * @param string $strategy recommendation strategy identifier
	 * @param string $operation recommendation operation identifier
	 * @param array &$options options passed for recommendation
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onRecommendation(&$state, &$request, $options) {
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
	 * @param CEM_GS_SimpleRequest &$request client request reference
	 * @param string $jump current jump identifier
	 * @param array &$variables contextual request variables
	 * @param array &$options options passed for interaction
	 * @return string final jump identifier
	 */
	protected function onInteractionBefore(&$state, &$request, $jump, &$variables, &$options) {
		return $jump;
	}

	/**
	 * Called after default interaction request is built
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_SimpleRequest &$request client request reference
	 * @param string $jump current jump identifier
	 * @param array &$variables contextual request variables
	 * @param array &$options options passed for interaction
	 * @return string final jump identifier
	 */
	protected function onInteractionAfter(&$state, &$request, $jump, &$variables, &$options) {
		return $jump;
	}
}


/**
 * Default CEM response handler for web-sites (CEM 1.4)
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebResponseHandler14 extends CEM_WebHandler14 {
	/**
	 * Main group identifier (defaults to 'main')
	 *
	 * @var string
	 */
	protected $mainGroupId;

	/**
	 * Current response
	 *
	 * @var CEM_GS_SimpleResponse
	 */
	protected $response;

	/**
	 * Current json response
	 *
	 * @var object
	 */
	protected $json;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $options context options
	 */
	public function __construct(&$crypto, $options = array()) {
		parent::__construct($crypto, $options);
		$this->mainGroupId = isset($options['mainGroupId']) ? $options['mainGroupId'] : 'main';
		$this->response = NULL;
		$this->json = NULL;
	}


	/**
	 * Get underlying response
	 *
	 * @return CEM_GS_SimpleResponse underlying response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get decoded json response
	 *
	 * @return object decoded json response
	 */
	public function getJson() {
		return $this->json;
	}

	/**
	 * Get context scope names
	 *
	 * @return array context scope names
	 */
	public function getContextScopes() {
		return $this->response->getContextScopes();
	}

	/**
	 * Get context scope
	 *
	 * @param string $name context name
	 * @return mixed context scope
	 */
	public function getContextScope($name) {
		return $this->response->getContextScope($name);
	}

	/**
	 * Get group
	 *
	 * @param string $id group identifier (defaults to main group)
	 * @return object group or NULL if none
	 */
	public function getGroup($id = NULL) {
		if ($this->json && isset($this->json->groups)) {
			if ($id == NULL) {
				$id = $this->mainGroupId;
			}
			if (isset($this->json->groups->$id)) {
				return $this->json->groups->$id;
			}
		}
		return NULL;
	}

	/**
	 * Get custom model
	 *
	 * @return object custom model or NULL if none
	 */
	public function getModel() {
		if ($this->json && isset($this->json->model)) {
			return $this->json->model;
		}
		return NULL;
	}

	/**
	 * Get decoded raw json response
	 *
	 * @param string $widget widget identifier (template.instance)
	 * @return string widget data (source or text)
	 */
	public function getWidgetData($widget) {
		list($template, $instance) = explode('.', $widget);

		if ($this->response) {
			return $this->response->getWidget($template, $instance);
		}
		return "";
	}


	/**
	 * Called each client interaction to wrap the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GS_SimpleRequest &$request client request reference
	 * @param CEM_GS_SimpleResponse &$response client response reference
	 * @param array &$options options passed for interaction
	 * @return mixed wrapped response on success or FALSE on error
	 */
	public function onInteraction(&$state, &$request, &$response, &$options) {
		$this->response = $response;

		// decode json response
		if ($this->response->getSize() > 0) {
			$this->json = json_decode($this->response->getResponse());
			if (!$this->json) {
				return FALSE;
			}
		}
		return $this;
	}

	/**
	 * Called each client recommendation to wrap the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_AbstractQuery &$request client request reference
	 * @param CEM_PR_SimpleResponse &$response client response reference
	 * @param string $strategy recommendation strategy identifier
	 * @param array &$options options passed for recommendation
	 * @return mixed wrapped response on success or FALSE on error
	 */
	public function onRecommendation(&$state, &$request, &$response, &$options) {
		return $response->getJson();
	}

	/**
	 * Called if client interaction triggers an error
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_SimpleRequest|CEM_GS_SimpleRequest &$request client request reference
	 * @param array &$options options passed for recommendation
	 */
	public function onError(&$state, &$request, &$options) {
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
	 * @var mixed
	 */
	protected $lastInteraction;


	/**
	 * Constructor
	 *
	 * @param array $options options map
	 */
	public function __construct($options = array()) {
		$this->url = 'http://root:@localhost:9000';
		$this->customer = 'default';
		$this->index = 'default';
		$this->language = 'en';
		$this->dialog = 'search';
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
	 * Process client interaction
	 *
	 * @param array &$options interaction options passed to handlers
	 * @param boolean $withResponse with response flag (defaults to TRUE)
	 * @param boolean $useCache optional parameter to use cache (defaults to TRUE)
	 * @return mixed wrapped cem response or FALSE on error
	 */
	public function interact(&$options = array(), $withResponse = TRUE, $useCache = TRUE) {
		// return cached interaction if any
		if ($useCache && $this->lastInteraction !== NULL) {
			return $this->lastInteraction;
		}

		// build cem state/request/response
		$request = new CEM_GS_SimpleRequest($this->customer, $this->dialog, $this->language, $withResponse);
		$response = new CEM_GS_SimpleResponse();
		list($state, $created) = $this->getState();

		// initialize client state
		if ($created) {
			if ($this->requestHandler) {
				if (!$this->requestHandler->onInit($state, $request)) {
					return FALSE;
				}
			} else {
				$request->appendInitRequest();
			}
		}

		// notify request handler
		if ($this->requestHandler && !$this->requestHandler->onInteraction($state, $request, $options)) {
			return FALSE;
		}

		// process interaction
		$client = new CEM_GatewayClient($this->url . '/gs/gateway/client-1.4', $this->connectionTimeout, $this->readTimeout);
		if (!$client->process($state, $request, $response)) {
			if ($this->responseHandler) {
				$this->responseHandler->onError($state, $request, $options);
			}
			return FALSE;
		}

		// notify response handler
		if ($this->responseHandler) {
			$this->lastInteraction = $this->responseHandler->onInteraction($state, $request, $response, $options);
		} else {
			$this->lastInteraction = $response;
		}

		// write client state
		if ($this->stateHandler) {
			$this->stateHandler->write($state);
		}
		return $this->lastInteraction;
	}


	/**
	 * Recommend items (customized request&response wrappers)
	 *
	 * @param string $strategy recommendation strategy identifier
	 * @param CEM_PR_AbstractQuery $request recommendation request
	 * @param CEM_PR_SimpleResponse $response recommendation response
	 * @param array &$options recommendation options
	 * @return mixed wrapped cem response or FALSE on error
	 */
	public function recommend(&$request, &$response, &$options = array()) {
		// build cem state
		list($state, $created) = $this->getState();

		// notify request handler
		if ($this->requestHandler && !$this->requestHandler->onRecommendation($state, $request, $options)) {
			return FALSE;
		}

		// process recommendation
		$client = new CEM_GatewayClient($this->url . '/pr/gateway/json-recommendation-1.4', $this->connectionTimeout, $this->readTimeout);
		if (!$client->process($state, $request, $response)) {
			if ($this->responseHandler) {
				$this->responseHandler->onError($state, $request, $options);
			}
			return FALSE;
		}

		// notify response handler
		if ($this->responseHandler) {
			return $this->responseHandler->onRecommendation($state, $request, $response, $options);
		}
		return $response;
	}

	/**
	 * Do query completion suggestion
	 *
	 * @param string $query query prefix to complete
	 * @param integer $size recommendation count (defaults to 5)
	 * @param integer $contextual contextual product count (defaults to 3)
	 * @param boolean $whitespace join words with whitespace (defaults to TRUE)
	 * @param array $attributePriorities fixed attribute priorities for disambiguation
	 * @param array &$options recommendation options
	 * @return mixed wrapped cem response or FALSE on error
	 */
	public function suggest($query, $size = 5, $contextual = 3, $whitespace = TRUE, $attributePriorities = array(), &$options = array()) {
		$request = new CEM_PR_MultiRequest($this->customer);
		$request->addRequest(
			new CEM_PR_CompletionQuery(
				'kb/query',
				'complete',
				$this->index,
				$this->language,
				'@type:instance',
				$query,
				$size,
				array(),
				array('title')
			)
		);
		$response = new CEM_PR_SimpleResponse();
		return $this->recommend($request, $response, $options);
	}


	/**
	 * Destroy client state gracefully if any
	 *
	 * @return mixed wrapped cem response on success or FALSE on error
	 */
	public function destroy() {
		// build cem state/request/response
		$request = new CEM_GS_SimpleRequest($this->customer, $this->dialog, $this->language, FALSE);
		$response = new CEM_GS_SimpleResponse();
		list($state, $created) = $this->getState();
		if ($created) {
			return FALSE;
		}

		// notify request handler
		if ($this->requestHandler) {
			if (!$this->requestHandler->onFree($state, $request)) {
				return FALSE;
			}
		} else {
			$request->appendFreeRequest();
		}

		// process interaction
		$client = new CEM_GatewayClient($this->url . '/gs/gateway/client-1.4', $this->connectionTimeout, $this->readTimeout);
		if (!$client->process($state, $request, $response)) {
			if ($this->responseHandler) {
				$this->responseHandler->onError($state, $request, array());
			}
			return FALSE;
		}

		// notify response handler
		if ($this->responseHandler) {
			$response = $this->responseHandler->onInteraction($state, $request, $response, array());
		}

		// clear client state
		if ($this->stateHandler) {
			$this->stateHandler->remove($state);
		}
		$this->lastInteraction = NULL;
		return $response;
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