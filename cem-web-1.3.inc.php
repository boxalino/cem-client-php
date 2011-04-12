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
 * Cookie-based CEM state handler for web-sites (CEM 1.3)
 *
 * @author nitro@boxalino.com
 */
class CEM_WebStateCookieHandler extends CEM_WebStateHandler {
	/**
	 * State variable key
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Expiry time
	 *
	 * @var integer
	 */
	protected $expiry;

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
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param string $key variable key (defaults to 'CEM')
	 * @param integer $expiry expiry time in seconds (30 minutes)
	 * @param string $path path (defaults to '/')
	 * @param string $domain domain (defaults to any)
	 * @param boolean $secure secure flag (defaults to FALSE)
	 */
	public function __construct(&$crypto, $key = 'CEM', $expiry = 1800, $path = '/', $domain = FALSE, $secure = FALSE) {
		parent::__construct($crypto);
		$this->key = $key;
		$this->expiry = $expiry;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;

		if (isset($_COOKIE[$this->key])) {
			$data = $this->decrypt($_COOKIE[$this->key]);
			if ($data) {
				$this->state = new CEM_GatewayState();

				// decode state
				list($cookies, $context, $others) = explode('&', $data);

				if (strlen($cookies) > 0) {
					foreach (explode(';', $cookies) as $item) {
						list($name, $value) = explode('=', $item);
	
						$name = urldecode($name);
						if (strlen($name) > 0) {
							$this->cookies[$name] = array('value' => urldecode($value));
						}
					}
				}
				if (strlen($context) > 0) {
					$value = array();
					foreach (explode(';', $context) as $item) {
						list($name, $level, $mode, $data) = explode('=', $item);
	
						$name = urldecode($name);
						$level = urldecode($level);
						$mode = urldecode($mode);
						$data = urldecode($data);
						if (strlen($name) > 0) {
							$value[$name] = array('level' => $level, 'mode' => $mode, 'data' => $data);
						}
					}
					$this->data['context'] = $value;
				}
				if (strlen($others) > 0) {
					foreach (explode(';', $others) as $item) {
						list($name, $data) = explode('=', $item);

						$name = urldecode($name);
						if (strlen($name) > 0) {
							$this->data[$name] = json_decode(urldecode($data));
						}
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
		$text = $state->getCookieHeader();
		$text .= '&';
		$context = $state->get('context');
		if (is_array($context)) {
			$i = 0;
			foreach ($context as $key => $value) {
				if ($i > 0) {
					$text .= ';';
				}
				$text .= urlencode($key) . '=' . urlencode($value['level']) . '=' . urlencode($value['mode']) . '=' . urlencode($value['data']);
				$i++;
			}
		}
		$text .= '&';
		$i = 0;
		foreach ($state->getAll() as $key => $value) {
			if ($key != 'context') {
				if ($i > 0) {
					$text .= ';';
				}
				$text .= urlencode($key) . '=' . urlencode(json_encode($value));
				$i++;
			}
		}
		$data = $this->encrypt($text);
		if ($data) {
			setcookie($this->key, $data, time() + $this->expiry, $this->path, $this->domain, $this->secure);
		} else {
			setcookie($this->key, "", time() - 3600, $this->path, $this->domain, $this->secure);
		}

		parent::write($state);
	}

	/**
	 * Remove client state from storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function remove(&$state) {
		setcookie($this->key, "", time() - 3600, $this->path, $this->domain, $this->secure);

		parent::remove($state);
	}
}

/**
 * Abstract CEM handler for web-sites (CEM 1.3)
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_WebHandler extends CEM_AbstractWebHandler {
	/**
	 * Context variables mapping in raw format (context -> CEM)
	 *
	 * @var array
	 */
	protected static $RAW_CONTEXT2CEM = array(
		'c' => 'container',
		'h' => 'hash',
		'q' => 'query',
		'ps' => 'pageSize'
	);

	/**
	 * Context variables mapping in raw format (CEM -> context)
	 *
	 * @var array
	 */
	protected static $RAW_CEM2CONTEXT = array(
		'container' => 'c',
		'hash' => 'h',
		'query' => 'q',
		'pageSize' => 'ps'
	);

	/**
	 * Context variables mapping in json format (context -> CEM)
	 *
	 * @var array
	 */
	protected static $JSON_CONTEXT2CEM = array(
		't' => 'terms'
	);

	/**
	 * Context variables mapping in json format (CEM -> context)
	 *
	 * @var array
	 */
	protected static $JSON_CEM2CONTEXT = array(
		'terms' => 't'
	);


	/**
	 * Request variables
	 *
	 * @var array
	 */
	protected $context;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $keys request parameter mapping
	 */
	public function __construct(&$crypto, $keys = array()) {
		parent::__construct($crypto, $keys);
		$this->context = array();

		$keys = array(
			'context' => 'ce',
			'offset' => 'o',
			'pageSize' => 'ps',
			'sort' => 's',
			'query' => 'q',
			'attribute' => 'a',
			'value' => 'v',
			'keyword' => 'k',
			'filter' => 'filter',
			'filters' => 'filters',
			'relaxation' => 'relaxation',
			'escalation' => 'escalation'
		);
		foreach ($keys as $key => $value) {
			if (!isset($this->keys[$key])) {
				$this->keys[$key] = $value;
			}
		}
	}


	/**
	 * Encode current context
	 *
	 * @return string raw context data or NULL if empty
	 */
	protected function encodeContext() {
		$data = '';
		foreach ($this->context as $key => $value) {
			if (isset(self::$RAW_CEM2CONTEXT[$key])) {
				if (strlen($data) > 0) {
					$data .= '|';
				}
				$data .= urlencode(self::$RAW_CEM2CONTEXT[$key]).'='.urlencode($value);
			} else if (isset(self::$JSON_CEM2CONTEXT[$key])) {
				if (strlen($data) > 0) {
					$data .= '|';
				}
				$data .= urlencode(self::$JSON_CEM2CONTEXT[$key]).'='.urlencode(json_encode($value));
			}
		}
		if (strlen($data) > 0) {
			$data = $this->crypto->encrypt(gzdeflate($data));
			if ($data) {
				return strtr(base64_encode($data), '+=/', '-_ ');
			}
		}
		return NULL;
	}

	/**
	 * Decode given context
	 *
	 * @param string $raw raw context data
	 * @return boolean TRUE on success or FALSE on failure
	 */
	protected function decodeContext($raw) {
		// check for empty context
		if (strlen($raw) == 0) {
			return FALSE;
		}

		// decode context
		$data = $this->crypto->decrypt(base64_decode(strtr($raw, '-_ ', '+=/')));
		if (!$data) {
			return FALSE;
		}
		$data = gzinflate($data);
		if (!$data) {
			return FALSE;
		}

		// apply context
		foreach (explode('|', $data) as $item) {
			list($key, $value) = explode('=', $item);
			$key = urldecode($key);
			if (isset(self::$RAW_CONTEXT2CEM[$key])) {
				$this->context[self::$RAW_CONTEXT2CEM[$key]] = urldecode($value);
			} else if (isset(self::$JSON_CONTEXT2CEM[$key])) {
				$this->context[self::$JSON_CONTEXT2CEM[$key]] = json_decode(urldecode($value));
			}
		}
		return TRUE;
	}
}


/**
 * Default CEM request handler for web-sites (CEM 1.3)
 *
 * @author nitro@boxalino.com
 */
class CEM_WebRequestHandler extends CEM_WebHandler {
	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $options context options
	 */
	public function __construct(&$crypto, $options = array()) {
		parent::__construct($crypto, isset($options['keys']) ? $options['keys'] : array());

		// initial context values if given
		if (isset($options['context'])) {
			foreach ($options['context'] as $key => $value) {
				$this->context[$key] = $value;
			}
		}
		$this->init();
	}


	/**
	 * Set context variables
	 *
	 * @param array $context context variables
	 */
	public function setContext($context) {
		foreach ($context as $key => $value) {
			$this->context[$key] = $value;
		}
	}


	/**
	 * Called to initialize the handler
	 *
	 */
	public function init() {
		// decode context if available
		if ($this->requestExists('context')) {
			$this->decodeContext($this->requestString('context'));
		}

		// override with request parameters
		if ($this->requestExists('offset')) {
			$this->context['offset'] = $this->requestNumber('offset');
		}
		if ($this->requestExists('pageSize')) {
			$this->context['pageSize'] = $this->requestNumber('pageSize');
		}
		if ($this->requestExists('sort')) {
			$this->context['sort'] = $this->requestString('sort');
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
		$jump = $this->onInteractionBefore($state, $request, 'set', $variables, $options);

		// controller logic
		if ($this->requestExists('query')) {
			// new textual query
			$jump = 'query';
			$variables['newQuery'] = $this->requestString('query');
		} else if ($this->requestExists('attribute') || $this->requestExists('keyword')) {
			// attribute/keyword filter manipulation
			$jump = 'changeFilters';
			if ($this->requestExists('attribute')) {
				$attribute = $this->requestString('attribute');
				$value = $this->requestString('value');
				if (strpos($attribute, '+') === 0 || strpos($attribute, ' ') === 0) {
					$variables['mode'] = 'add';
					$variables['attribute'] = substr($attribute, 1);
				} else if (strpos($attribute, '-') === 0) {
					$variables['mode'] = 'del';
					$variables['attribute'] = substr($attribute, 1);
				} else {
					$variables['mode'] = 'set';
					$variables['attribute'] = $attribute;
				}
				if (strlen($value) > 0) {
					if (strpos($value, ' ') > 0) {
						$variables['values'] = explode(' ', $value);
					} else {
						$variables['value'] = $value;
					}
				} else {
					$variables['mode'] = 'del';
				}
			}
			if ($this->requestExists('keyword')) {
				$keyword = $this->requestString('keyword');
				if (strpos($keyword, '+') === 0 || strpos($keyword, ' ') === 0) {
					$variables['mode'] = 'add';
					$keyword = substr($keyword, 1);
				} else if (strpos($keyword, 'x') === 0) {
					$variables['mode'] = 'exclude';
					$keyword = substr($keyword, 1);
				} else if (strpos($keyword, '-') === 0) {
					$variables['mode'] = 'del';
					$keyword = substr($keyword, 1);
				} else {
					$variables['mode'] = 'set';
				}
				if (strpos($keyword, ' ') > 0) {
					$keywords = array();
					foreach (explode(' ', $keyword) as $item) {
						$keywords[] = intval($item);
					}
					$variables['keywords'] = $keywords;
				} else {
					$variables['keyword'] = intval($keyword);
				}
			}
		} else if ($this->requestExists('escalation')) {
			$jump = 'escalation';
			$variables['payloads'] = array();
			foreach (explode(',', $this->requestString('escalation')) as $key) {
				if ($this->requestExists($key)) {
					$variables['payloads']['_gs_'.$key.'_label'] = array(
						'type' => 'string',
						'value' => $key
					);
					$variables['payloads']['_gs_'.$key] = array(
						'type' => 'string',
						'value' => $this->requestString($key)
					);
				}
			}
		} else if ($this->requestExists('filters')) {
			$jump = 'setFilters';
			$variables['filters'] = json_decode($this->requestString('filters'));
		} else if ($this->requestBoolean('relaxation')) {
			$jump = 'relax';
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
		if (isset($options['widgets'])) {
			foreach ($options['widgets'] as $widget) {
				list($template, $instance) = explode('.', $widget);

				if (strlen($template) > 0 && strlen($instance) > 0) {
					$request->addWidget($template, $instance, CEM_GS_WIDGET_DATA);
				}
			}
		}

		// notify custom implementation
		$jump = $this->onInteractionAfter($state, $request, $jump, $variables, $options);

		// add final request
		$request->appendRequest($jump, $variables);

		// request state widget
		$request->addWidget('state', 'default', CEM_GS_WIDGET_DATA);
		return TRUE;
	}

	/**
	 * Called each client recommendation to build request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_SimpleRequest &$request client request reference
	 * @param string $strategy recommendation strategy identifier
	 * @param array &$options options passed for recommendation
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function onRecommendation(&$state, &$request, $strategy, $options) {
		if (isset($this->context['properties'])) {
			$request->setIds('properties', $this->context['properties']);
		}
		if (isset($this->context['attributes'])) {
			$request->setIds('attributes', $this->context['attributes']);
		}
		if (isset($this->context['keywords'])) {
			$request->setNumbers('keywords', $this->context['keywords']);
		}
		switch ($strategy) {
		case 'gsQuerySuggestion':
			$request-> setString('query', $options['query']);
			$request-> setNumber('distanceThreshold', strlen($options['query']) < 10 ? (strlen($options['query']) / 5) + 1 : 3);
			$request->setBoolean('matchAttributes', TRUE);
			$request->setBoolean('matchKeywords', TRUE);
			$request->setBoolean('matchContents', $options['matchContents']);
			$request->setBoolean('withSpace', $options['whitespace']);
			$request->setBoolean('alwaysComplete', $options['alwaysComplete']);
			$request-> setNumber('contextualCount', $options['contextual']);
			$request-> setNumber('prefixThreshold', $options['prefixThreshold']);
			$request->    setIds('priorities', $options['attributePriorities']);
			if (isset($this->context['population'])) {
				$request->setNumber('population', $this->context['population']);
			}
			if (isset($options['search'])) {
				$request->setSearch('search', $options['search']);
			}
			break;

		case 'prPreferences':
			if (isset($this->context['hash'])) {
				$request->setString('hash', $this->context['hash']);
			}
			if ($this->requestExists('attribute') || $this->requestExists('keyword')) {
				$search = array(
					'filters' => array(),
					'scorers' => array(),
					'ranking' => array()
				);
				if ($this->requestExists('attribute')) {
					$mode = 'any';
					$attribute = $this->requestString('attribute');
					$value = $this->requestString('value');
					if (strpos($attribute, '+') === 0 || strpos($attribute, ' ') === 0) {
						$attribute = substr($attribute, 1);
					} else if (strpos($attribute, '-') === 0) {
						$mode = 'notAny';
						$attribute = substr($attribute, 1);
					}
					$search['filters'][] = array(
						'type' => 'attribute',
						'mode' => $mode,
						'attribute' => $attribute,
						'values' => explode(' ', $this->requestString('value'))
					);
				}
				if ($this->requestExists('keyword')) {
					$mode = 'any';
					$keyword = $this->requestString('keyword');
					if (strpos($keyword, '+') === 0 || strpos($keyword, ' ') === 0) {
						$keyword = substr($keyword, 1);
					} else if (strpos($keyword, '-') === 0 || strpos($keyword, 'x') === 0) {
						$mode = 'notAny';
						$keyword = substr($keyword, 1);
					}
					$search['filters'][] = array(
						'type' => 'keyword',
						'mode' => $mode,
						'keywords' => explode(' ', $keyword)
					);
				}
				$request->setSearch('search', $search);
			}
			break;
		}
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
 * Default CEM response handler for web-sites (CEM 1.3)
 *
 * @author nitro@boxalino.com
 */
class CEM_WebResponseHandler extends CEM_WebHandler {
	/**
	 * Main search group identifier (defaults to 'main')
	 *
	 * @var string
	 */
	protected $mainSearchId;

	/**
	 * Current request
	 *
	 * @var CEM_GS_SimpleRequest
	 */
	protected $request;

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
	 * Current context value
	 *
	 * @var string
	 */
	protected $contextValue;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $options context options
	 */
	public function __construct(&$crypto, $options = array()) {
		parent::__construct($crypto, isset($options['keys']) ? $options['keys'] : array());
		$this->mainSearchId = isset($options['mainSearchId']) ? $options['mainSearchId'] : 'main';
		$this->response = NULL;
		$this->json = NULL;
		$this->contextValue = NULL;
	}


	/**
	 * Get encoded context
	 *
	 * @param boolean $encoded url encode flag
	 * @return string encoded context or "" if none
	 */
	public function getContext($encoded = FALSE) {
		if ($this->contextValue) {
			if ($encoded) {
				return urlencode($this->contextValue);
			}
			return $this->contextValue;
		}
		return '';
	}

	/**
	 * Build url cem query part (after '?')
	 *
	 * @param array $parameters initial parameters (defaults to empty)
	 * @param boolean $withContext add also context value (defaults to TRUE)
	 * @return string url query part
	 */
	public function buildQuery($parameters = array(), $withContext = TRUE) {
		$query = '';
		if ($withContext) {
			if (isset($parameters[$this->keys['context']])) {
				if (strlen($parameters[$this->keys['context']]) == 0) {
					unset($parameters[$this->keys['context']]);
				}
			} else if ($this->contextValue != NULL) {
				$parameters[$this->keys['context']] = $this->contextValue;
			}
		} else {
			unset($parameters[$this->keys['context']]);
		}
		if (isset($parameters[$this->keys['offset']])) {
			if ($parameters[$this->keys['offset']] <= 0) {
				unset($parameters[$this->keys['offset']]);
			}
		} else if (isset($this->context['offset'])) {
			$parameters[$this->keys['offset']] = $this->context['offset'];
		}
		if (isset($parameters[$this->keys['sort']])) {
/*			if ($parameters[$this->keys['sort']] == $this->defaultSort) {
				unset($parameters[$this->keys['sort']]);
			}*/
		} else if (isset($this->context['sort'])) {
			$parameters[$this->keys['sort']] = $this->context['sort'];
		}
		foreach ($parameters as $key => $value) {
			if (strlen($key) > 0 && strlen($value) > 0) {
				if (strlen($query) > 0) {
					$query .= '&';
				}
				$query .= urlencode($key) . '=' . urlencode($value);
			}
		}
		return $query;
	}


	/**
	 * Get underlying request
	 *
	 * @return CEM_GS_SimpleRequest underlying request
	 */
	public function getRequest() {
		return $this->request;
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
	 * Get search group
	 *
	 * @param string $id group identifier (defaults to main group)
	 * @return object search group or NULL if none
	 */
	public function getSearch($id = NULL) {
		if ($this->json && isset($this->json->searches)) {
			if ($id == NULL) {
				$id = $this->mainSearchId;
			}
			if (isset($this->json->searches->$id)) {
				return $this->json->searches->$id;
			}
		}
		return NULL;
	}

	/**
	 * Get recommendation group
	 *
	 * @param string $id group identifier
	 * @return object recommendation group or NULL if none
	 */
	public function getRecommendation($id) {
		if ($this->json && isset($this->json->recommendations)) {
			if (isset($this->json->recommendations->$id)) {
				return $this->json->recommendations->$id;
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
		$this->request = $request;
		$this->response = $response;

		// decode json response
		if ($this->response->getSize() > 0) {
			$this->json = json_decode($this->response->getResponse());
			if (!$this->json) {
				return FALSE;
			}
		}

		// build context from state widget
		$widget = $this->response->getWidget('state', 'default');
		if (strlen($widget) > 0) {
			$context = json_decode($widget);
			if ($context) {
				foreach ($context as $key => $value) {
					$this->context[$key] = $value;
				}
			}
		}
		$this->contextValue = $this->encodeContext();
		return $this;
	}

	/**
	 * Called each client recommendation to wrap the response
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_PR_SimpleResponse &$response client response reference
	 * @param string $strategy recommendation strategy identifier
	 * @param array &$options options passed for recommendation
	 * @return mixed wrapped response on success or FALSE on error
	 */
	public function onRecommendation(&$state, &$response, $strategy, &$options) {
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
 * CEM controller for web-sites (CEM 1.3)
 *
 * @author nitro@boxalino.com
 */
class CEM_WebController {
	/**
	 * CEM server url (defaults to 'http://root:@localhost:9000')
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Customer (defaults to 'default')
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Container (defaults to 'default')
	 *
	 * @var string
	 */
	protected $container;

	/**
	 * Language (defaults to 'en')
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Machine (defaults to 'search')
	 *
	 * @var string
	 */
	protected $machine;

	/**
	 * State handler
	 *
	 * @var CEM_WebStateHandler
	 */
	protected $stateHandler;

	/**
	 * Request handler
	 *
	 * @var CEM_WebRequestHandler
	 */
	protected $requestHandler;

	/**
	 * Response handler
	 *
	 * @var CEM_WebResponseHandler
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
		$this->container = 'default';
		$this->language = 'en';
		$this->machine = 'search';
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
	 * Get request handler
	 *
	 * @return CEM_WebRequestHandler request handler
	 */
	public function getRequestHandler() {
		return $this->requestHandler;
	}

	/**
	 * Get response handler
	 *
	 * @return CEM_WebResponseHandler response handler
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
		$request = new CEM_GS_SimpleRequest($this->customer, $this->machine, $this->language, $withResponse);
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
		$client = new CEM_GatewayClient($this->url . '/gs/gateway/client', $this->connectionTimeout, $this->readTimeout);
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
	 * Recommend items
	 *
	 * @param string $strategy recommendation strategy identifier
	 * @param integer $offset recommendation offset (defaults to 0)
	 * @param integer $size recommendation count (defaults to 5)
	 * @param array &$options recommendation options
	 * @return mixed wrapped cem response or FALSE on error
	 */
	public function recommend($strategy, $offset = 0, $size = 5, &$options = array()) {
		// build cem state/request/response
		$request = new CEM_PR_SimpleRequest($strategy, $offset, $size);
		$request->setId('customer', $this->customer);
		$request->setId('container', $this->container);
		$request->setId('language', $this->language);
		$response = new CEM_PR_SimpleResponse();
		list($state, $created) = $this->getState();

		// notify request handler
		if ($this->requestHandler && !$this->requestHandler->onRecommendation($state, $request, $strategy, $options)) {
			return FALSE;
		}

		// process recommendation
		$client = new CEM_GatewayClient($this->url . '/pr/gateway/json-recommendation', $this->connectionTimeout, $this->readTimeout);
		if (!$client->process($state, $request, $response)) {
			if ($this->responseHandler) {
				$this->responseHandler->onError($state, $request, $options);
			}
			return FALSE;
		}

		// notify response handler
		if ($this->responseHandler) {
			return $this->responseHandler->onRecommendation($state, $response, $strategy, $options);
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
		$options['query'] = $query;
		$options['contextual'] = $contextual;
		$options['whitespace'] = $whitespace;
		$options['attributePriorities'] = $attributePriorities;
		if (!isset($options['prefixThreshold'])) {
			$options['prefixThreshold'] = 1;
		}
		if (!isset($options['alwaysComplete'])) {
			$options['alwaysComplete'] = FALSE;
		}
		if (!isset($options['matchContents'])) {
			$options['matchContents'] = FALSE;
		}
		return $this->recommend('gsQuerySuggestion', 0, $size, $options);
	}

	/**
	 * Do overlay recommendation
	 *
	 * @param integer $size recommendation count (defaults to 3)
	 * @param array &$options recommendation options
	 * @return mixed wrapped cem response or FALSE on error
	 */
	public function overlay($size = 3, &$options = array()) {
		return $this->recommend('prPreferences', 0, $size, $options);
	}


	/**
	 * Destroy client state gracefully if any
	 *
	 * @return mixed wrapped cem response on success or FALSE on error
	 */
	public function destroy() {
		// build cem state/request/response
		$request = new CEM_GS_SimpleRequest($this->customer, $this->machine, $this->language, FALSE);
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
		$client = new CEM_GatewayClient($this->url . '/gs/gateway/client', $this->connectionTimeout, $this->readTimeout);
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

/**
 * @}
 */

?>