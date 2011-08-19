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
	protected $context;

	/**
	 * Sequential contexts
	 */
	protected $sequentialContexts;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param $keys request parameter mapping
	 */
	public function __construct(&$crypto, $keys = array()) {
		parent::__construct($crypto, $keys);
		$this->sequentialContexts = array();
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
	 * Get sequential context variables
	 *
	 * @param $name context name
	 * @return context variables
	 */
	public function getSequentialContext($name) {
		if (isset($this->sequentialContexts[$name])) {
			return $this->sequentialContexts[$name]['data'];
		}
		return '';
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
		$model = isset($contexts['model']) ? json_decode($contexts['model']['data']) : array();

		// get cem model context
		$userState = isset($contexts['userState']) ? json_decode($contexts['userState']['data']) : array();

		// notify custom implementation
		$variables = $this->buildInteractionVariables($options);
		$action = $this->onInteractionBefore($state, $request, 'none', $variables, $options);

		// base parameters
		if ($this->requestExists('offset')) {
			$variables['offset'] = $this->requestNumber('offset');
		}
		if ($this->requestExists('pageSize')) {
			$variables['pageSize'] = $this->requestNumber('pageSize');
		} else if (isset($model->pageSize)) {
			$variables['pageSize'] = $model->pageSize;
		} else if (isset($userState->pageSize)) {
			$variables['pageSize'] = $userState->pageSize;
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
		} else if (isset($userState->ranking)) {
			$query['ranking'] = $userState->ranking;
		}
		if (sizeof($query) > 0) {
			$variables['query'] = $query;
		}

		// scenario
		if ($this->requestExists('scenario')) {
			$variables['scenario'] = $this->requestString('scenario');
		} else if (isset($model->scenario)) {
			$variables['scenario'] = $model->scenario;
		} else if (isset($userState->scenario)) {
			$variables['scenario'] = $userState->scenario;
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

		// add detail request
		if ($this->requestExists('detail')) {
			// notify custom implementation
			$variables = $this->buildInteractionVariables($options);
			$action = $this->onInteractionBefore($state, $request, 'detail', $variables, $options);

			// controller logic
			$variables['sourceFilter'] = '@type:instance&@id:"'.addcslashes($this->requestString('detail'), '"').'"';

			// notify custom implementation
			$action = $this->onInteractionAfter($state, $request, $action, $variables, $options);

			// add final request
			$request->appendRequest($action, $variables);
		}

		// add state request
		$userState = array();
		if ($this->requestExists('pageSize')) {
			$userState['pageSize'] = $this->requestNumber('pageSize');
		}
		if ($this->requestExists('ranking')) {
			$userState['ranking'] = $this->requestString('ranking');
		}
		if ($this->requestExists('displayMode')) {
			$userState['displayMode'] = $this->requestString('displayMode');
		}
		if ($this->requestExists('scenario')) {
			$userState['scenario'] = $this->requestString('scenario');
		}
		if (sizeof($userState) > 0) {
			// notify custom implementation
			$variables = array();
			$action = $this->onInteractionBefore($state, $request, 'setUserState', $variables, $options);

			// controller logic
			$variables['userState'] = $userState;

			// notify custom implementation
			$action = $this->onInteractionAfter($state, $request, $action, $variables, $options);

			// add final request
			$request->appendRequest($action, $variables);
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
		$variables = array();
		foreach ($this->context as $key => $value) {
			$variables[$key] = $value;
		}
		return $variables;
	}

	/**
	 * Called before default interaction request is built
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param $action current action identifier
	 * @param &$variables contextual request variables
	 * @param &$options options passed for interaction
	 * @return final action identifier
	 */
	protected function onInteractionBefore(&$state, &$request, $action, &$variables, &$options) {
		return $action;
	}

	/**
	 * Called after default interaction request is built
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param $action current action identifier
	 * @param &$variables contextual request variables
	 * @param &$options options passed for interaction
	 * @return final action identifier
	 */
	protected function onInteractionAfter(&$state, &$request, $action, &$variables, &$options) {
		return $action;
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