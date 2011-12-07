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
 * Default CEM request handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebRequestHandler extends CEM_AbstractWebHandler {
	/**
	 * Request variables
	 */
	protected $context = array();

	/**
	 * Sequential contexts
	 */
	protected $sequentialContexts = array();

	/**
	 * Current user state
	 */
	protected $userState = array();

	/**
	 * User state keys
	 */
	protected $userStateKeys = array('pageSize' => 'number', 'ranking' => 'string', 'scenario' => 'string');

	/**
	 * Current model
	 */
	protected $model = array();


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param $keys request parameter mapping
	 */
	public function __construct(&$crypto, $keys = array()) {
		parent::__construct($crypto, $keys);
		$this->parseSequentialContexts();
	}


	/**
	 * Get request variables
	 *
	 * @return request variables
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Set request variables
	 *
	 * @param $context request variables
	 * @return this
	 */
	public function setContext($context) {
		foreach ($context as $key => $value) {
			$this->context[$key] = $value;
		}
		return $this;
	}


	/**
	 * Get sequential contexts
	 *
	 * @return sequential contexts
	 */
	public function getSequentialContexts() {
		return $this->sequentialContexts;
	}

	/**
	 * Get sequential context data
	 *
	 * @param $name context name
	 * @return context data
	 */
	public function getSequentialContext($name) {
		if (isset($this->sequentialContexts[$name])) {
			return $this->sequentialContexts[$name]['data'];
		}
		return '';
	}

	/**
	 * Set sequential context variables
	 *
	 * @param $name context name
	 * @param $data context data
	 */
	public function setSequentialContext($name, $data) {
		if (isset($this->sequentialContexts[$name])) {
			$this->sequentialContexts[$name]['data'] = $data;
		} else {
			$this->sequentialContexts[$name] = array(
				'level' => 'search',
				'mode' => 'sequential',
				'data' => $data
			);
		}
	}

	/**
	 * Parse sequential context variables
	 *
	 * @param $key context key
	 */
	public function parseSequentialContexts($key = 'context') {
		// decode sequential context states
		if ($this->requestExists($key)) {
			$data = $this->decrypt($this->requestString($key));
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
	 * Register user states
	 *
	 * @param $key request variables
	 * @return this
	 */
	public function registerUserStateKeys($keys) {
		foreach ($keys as $key => $type) {
			$this->userStateKeys[$key] = $type;
		}
		return $this;
	}


	/**
	 * Called when client state needs to be initialized
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @return TRUE on success or FALSE on error
	 */
	public function onInit(&$state, &$request) {
		$request->appendInitRequest();
		return TRUE;
	}

	/**
	 * Called when client state needs to be freed
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @return TRUE on success or FALSE on error
	 */
	public function onFree(&$state, &$request) {
		$request->appendFreeRequest();
		return TRUE;
	}

	/**
	 * Called each client interaction to build request
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param &$options options passed for interaction
	 * @return TRUE on success or FALSE on error
	 */
	public function onInteraction(&$state, &$request, &$options) {
		// merge contexts
		$contexts = $state->get('context', array());
		foreach ($this->sequentialContexts as $name => $value) {
			$contexts[$name] = $value;
		}
		$state->set('context', $contexts);

		// get cem model context
		$this->model = isset($contexts['model']) ? json_decode($contexts['model']['data']) : array();

		// get cem model context
		$this->userState = isset($contexts['userState']) ? json_decode($contexts['userState']['data']) : array();

		// add state request
		$userState = array();
		foreach ($this->userStateKeys as $key => $type) {
			if (!$this->requestExists($key)) {
				continue;
			}
			switch ($type) {
			case 'boolean':
				$userState[$key] = $this->requestBoolean($key);
				break;

			case 'number':
				$userState[$key] = $this->requestNumber($key);
				break;

			case 'array':
				$userState[$key] = $this->requestStringArray($key);
				break;

			default:
				$userState[$key] = $this->requestString($key);
				break;
			}
		}
		if (sizeof($userState) > 0) {
			$request->appendRequest('setUserState', array('userState' => $userState));
		}

		// add default request
		$extraSearch = FALSE;
		$action = 'search';
		$variables = $this->buildInteractionVariables($options);
		if (isset($options['batch'])) {
			foreach ($options['batch'] as $item) {
				$action = 'search';
				if (isset($item['action'])) {
					$action = $item['action'];
				}
				$variables = $this->buildInteractionVariables($options);
				if (isset($item['variables'])) {
					foreach ($item['variables'] as $key => $value) {
						$variables[$key] = $value;
					}
				}
				$request->appendRequest($action, $variables);
			}
		} else if (isset($options['action'])) {
			$action = strval($options['action']);
			switch ($options['action']) {
			case 'query':
			case 'refine':
			case 'clearQuery':
			case 'addGuidance':
			case 'setGuidance':
			case 'delGuidance':
				$extraSearch = TRUE;
				break;
			}
		} else if ($this->requestExists('detail')) {
			$action = 'detail';
			if ($this->requestExists('detailTarget')) {
				$variables['scenarioTarget'] = $this->requestString('detailTarget');
			}
			$variables['sourceFilter'] = '@type:instance&@id:"'.addcslashes($this->requestString('detail'), '"').'"';
		} else if ($this->requestExists('query')) {
			$action = 'query';
			$variables['queryText'] = $this->requestString('query');
			if ($this->requestExists('ac')) {
				$variables['ac'] = $this->requestNumber('ac');
			}
			$extraSearch = TRUE;
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
			$extraSearch = TRUE;
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
			$extraSearch = TRUE;
		} else if ($this->requestExists('feedback')) {
			$action = 'feedback';
			$variables['weight'] = $this->requestNumber('feedback');
		}
		$request->appendRequest($action, $variables);

		// add extra search request?
		if ($extraSearch) {
			$request->appendRequest('search', $this->buildInteractionVariables($options));
		}
		return TRUE;
	}

	/**
	 * Build interaction variables
	 *
	 * @param &$options options passed for interaction
	 * @return contextual request variables
	 */
	protected function buildInteractionVariables(&$options) {
		// context variables
		$variables = array();
		foreach ($this->context as $key => $value) {
			$variables[$key] = $value;
		}

		// base parameters
		if ($this->requestExists('offset')) {
			$variables['offset'] = $this->requestNumber('offset');
		}
		if ($this->requestExists('pageSize')) {
			$variables['pageSize'] = $this->requestNumber('pageSize');
		} else if (isset($this->model->pageSize)) {
			$variables['pageSize'] = $this->model->pageSize;
		} else if (isset($this->userState->pageSize)) {
			$variables['pageSize'] = $this->userState->pageSize;
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
		} else if (isset($this->model->ranking)) {
			$query['ranking'] = $this->model->ranking;
		} else if (isset($this->userState->ranking)) {
			$query['ranking'] = $this->userState->ranking;
		}
		if (sizeof($query) > 0) {
			$variables['query'] = $query;
		}

		// scenario
		if ($this->requestExists('scenario')) {
			$variables['scenario'] = $this->requestString('scenario');
		} else if (isset($this->model->scenario)) {
			$variables['scenario'] = $this->model->scenario;
		} else if (isset($this->userState->scenario)) {
			$variables['scenario'] = $this->userState->scenario;
		}

		// custom overrides
		if (isset($options['variables'])) {
			foreach ($options['variables'] as $key => $value) {
				$variables[$key] = $value;
			}
		}
		return $variables;
	}


	/**
	 * Called each client recommendation to build request
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param &$options options passed for recommendation
	 * @return TRUE on success or FALSE on error
	 */
	public function onRecommendation(&$state, &$request, $options) {
		return TRUE;
	}
}

/**
 * @}
 */

?>