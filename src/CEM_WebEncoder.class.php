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
 * Default CEM action encoder for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebEncoder {
	/**
	 * Encryption facility
	 */
	protected $crypto;

	/**
	 * Value formatter
	 */
	protected $formatter;

	/**
	 * Request variables
	 */
	protected $variables = array();

	/**
	 * User state keys
	 */
	protected $userStateKeys = array();


	/**
	 * Constructor
	 *
	 * @param $crypto encryption facility
	 * @param $formatter value formatter
	 */
	public function __construct($crypto, $formatter) {
		$this->crypto = $crypto;
		$this->formatter = $formatter;
	}


	/**
	 * Get context scopes
	 *
	 * @return context scopes
	 */
	public function getContext() {
		// TODO: implement this in a sub-class
		return array();
	}

	/**
	 * Set context scopes
	 *
	 * @param $context context scopes
	 * @return this
	 */
	public function setContext($context) {
		// TODO: implement this in a sub-class
		return $this;
	}


	/**
	 * Get value formatter
	 *
	 * @return value formatter
	 */
	public function getFormatter() {
		return $this->formatter;
	}


	/**
	 * Check if request variable exists
	 *
	 * @param $name variable name
	 * @return TRUE if exists, FALSE otherwise
	 */
	public function hasVariable($name) {
		return isset($this->variables[$name]);
	}

	/**
	 * Get request variable
	 *
	 * @param $name variable name
	 * @param $default default value
	 * @return variable value
	 */
	public function getVariable($name, $default = FALSE) {
		if (isset($this->variables[$name])) {
			return $this->variables[$name];
		}
		return $default;
	}

	/**
	 * Set request variable
	 *
	 * @param $name variable name
	 * @param $value variable value
	 * @return this
	 */
	public function setVariable($name, $value) {
		$this->variables[$name] = $value;
		return $this;
	}

	/**
	 * Remove request variable
	 *
	 * @param $name variable name
	 * @return this
	 */
	public function removeVariable($name) {
		if (isset($this->variables[$name])) {
			unset($this->variables[$name]);
		}
		return $this;
	}

	/**
	 * Get request variables
	 *
	 * @return variables values
	 */
	public function getVariables() {
		return $this->variables;
	}

	/**
	 * Set request variables
	 *
	 * @param $variables variables values
	 * @return this
	 */
	public function setVariables($variables) {
		foreach ($variables as $name => $value) {
			$this->variables[$name] = $value;
		}
		return $this;
	}

	/**
	 * Clear request variables
	 *
	 * @return this
	 */
	public function clearVariables() {
		$this->variables = array();
		return $this;
	}


	/**
	 * Check if user state exists
	 *
	 * @param $key state key
	 * @return TRUE if exists, FALSE otherwise
	 */
	public function hasUserState($key) {
		return isset($this->userStateKeys[$key]);
	}

	/**
	 * Get user state
	 *
	 * @param $key state key
	 * @return state type or FALSE if none
	 */
	public function getUserState($key) {
		if (isset($this->userStateKeys[$key])) {
			return $this->userStateKeys[$key];
		}
		return FALSE;
	}

	/**
	 * Set user state
	 *
	 * @param $key state key
	 * @param $info state info
	 * @return this
	 */
	public function setUserState($key, $info) {
		$this->userStateKeys[$key] = $info;
		return $this;
	}

	/**
	 * Remove user state
	 *
	 * @param $key state key
	 * @return this
	 */
	public function removeUserState($key) {
		if (isset($this->userStateKeys[$key])) {
			unset($this->userStateKeys[$key]);
		}
		return $this;
	}

	/**
	 * Get user states
	 *
	 * @return user states
	 */
	public function getUserStates() {
		return $this->userStateKeys;
	}

	/**
	 * Set user states
	 *
	 * @param $states state key/info
	 * @return this
	 */
	public function setUserStates($states) {
		foreach ($states as $key => $info) {
			$this->userStateKeys[$key] = $info;
		}
		return $this;
	}

	/**
	 * Clear user states
	 *
	 * @return this
	 */
	public function clearUserStates() {
		$this->userStateKeys = array();
		return $this;
	}


	/**
	 * Get context data
	 *
	 * @param $name context name
	 * @return context data or empty string if none
	 */
	public function getContextData($name) {
		$context = $this->getContext();
		if (isset($context[$name])) {
			return $context[$name]['data'];
		}
		return '';
	}

	/**
	 * Set context variables
	 *
	 * @param $name context name
	 * @param $data context data
	 */
	public function setContextData($name, $data) {
		$context = $this->getContext();
		if (isset($context[$name])) {
			$context[$name]['data'] = $data;
		} else {
			$context[$name] = array(
				'level' => 'search',
				'mode' => 'sequential',
				'data' => $data
			);
		}
		$this->setContext($context);
	}

	/**
	 * Encode sequential context
	 *
	 * @param $context context scopes
	 * @return encoded sequential context scopes or FALSE if none
	 */
	public function encodeSequentialContext($context) {
		$data = '';
		foreach ($context as $name => $scope) {
			if ($scope['mode'] != 'sequential') {
				continue;
			}
			switch ($scope['level']) {
			case 'visitor':
			case 'session':
			case 'search':
				if (strlen($data) > 0) {
					$data .= ';';
				}
				$data .= $this->crypto->escapeValue($name) . '=' . $this->crypto->escapeValue($scope['level']) . '=' . $this->crypto->escapeValue($scope['data']);
				break;
			}
		}
		return $this->crypto->encrypt64($data);
	}

	/**
	 * Decode sequential context
	 *
	 * @param $key context key
	 * @return sequential context
	 */
	public function decodeSequentialContext($value) {
		$context = array();

		// decrypt/deflate context scopes
		$value = $this->crypto->decrypt64($value);
		if ($value) {
			// decode sequential context states
			foreach (explode(';', $value) as $scope) {
				list($name, $level, $data) = explode('=', $scope);

				$name = $this->crypto->unescapeValue($name);
				$level = $this->crypto->unescapeValue($level);
				$data = $this->crypto->unescapeValue($data);
				$context[$name] = array(
					'level' => $level,
					'mode' => 'sequential',
					'data' => $data
				);
			}
		}
		return $context;
	}


	/**
	 * Called each client interaction to build state context scopes
	 *
	 * @param $state client state reference
	 * @param $options options passed for interaction
	 */
	public function buildStateContext($state, $options) {
		$context = array();
		foreach ($state->getContext() as $name => $value) {
			$context[$name] = $value;
		}
		foreach ($this->getContext() as $name => $value) {
			$context[$name] = $value;
		}
		if (isset($options['context'])) {
			foreach ($options['context'] as $name => $value) {
				if ($value) {
					$context[$name] = $value;
				} else if (isset($context[$name])) {
					unset($context[$name]);
				}
			}
		}
		$state->setContext($context);
	}

	/**
	 * Called each client interaction to build request
	 *
	 * @param $state client state reference
	 * @param $request client request reference
	 * @param $options options passed for interaction
	 */
	public function buildInteractionRequest($state, $request, $options) {
		$userState = $state->getContextJson('userState');
		$userStateChanged = FALSE;
		$model = $state->getContextJson('model');
		$modelChanged = FALSE;

		// add state request
		$userStateVariables = array();
		$modelStateVariables = array();
		foreach ($this->userStateKeys as $key => $info) {
			$value = $this->buildUserState($state, $request, $options, $key, $info);
			if ($value) {
				$userStateVariables[$key] = $value;

				// unset from stored user state
				if ($userState && isset($userState->$key)) {
					unset($userState->$key);
					$userStateChanged = TRUE;
				}
			}
			$value = $this->buildModelState($state, $request, $options, $key, $info);
			if ($value) {
				$modelStateVariables[$key] = $value;

				// unset from stored model state
				if ($model && isset($model->state->$key)) {
					unset($model->state->$key);
					$modelChanged = TRUE;
				}
			}
		}
		if (sizeof($userStateVariables) + sizeof($modelStateVariables) > 0) {
			$request->appendRequest(
				'setUserState',
				array(
					'userState' => $userStateVariables,
					'modelState' => $modelStateVariables
				)
			);

			if ($userStateChanged) {
				$state->setContextData('userState', @json_encode($userState));
			}
			if ($modelChanged) {
				$state->setContextData('model', @json_encode($model));
			}
		}
	}

	/**
	 * Called each client interaction to build response
	 *
	 * @param $state client state reference
	 * @param $response client response reference
	 * @param $options options passed for interaction
	 */
	public function buildInteractionResponse($state, $response, $options) {
	}

	/**
	 * Build user state if any
	 *
	 * @param $state client state reference
	 * @param $request client request reference
	 * @param $options options passed for interaction
	 * @param $key user state key
	 * @param $info user state info
	 * @return user state value if any, FALSE otherwise
	 */
	public function buildUserState($state, $request, $options, $key, $info) {
		return FALSE;
	}

	/**
	 * Build model state if any
	 *
	 * @param $state client state reference
	 * @param $request client request reference
	 * @param $options options passed for interaction
	 * @param $key user state key
	 * @param $info user state info
	 * @return model state value if any, FALSE otherwise
	 */
	public function buildModelState($state, $request, $options, $key, $info) {
		return FALSE;
	}

	/**
	 * Build interaction variables
	 *
	 * @param $state client state reference
	 * @param $request client request reference
	 * @param $options options passed for interaction
	 * @return contextual request variables
	 */
	public function buildInteractionVariables($state, $request, $options) {
		// context variables
		$variables = array();
		foreach ($this->variables as $key => $value) {
			$variables[$key] = $value;
		}
		return $variables;
	}


	/**
	 * Encode parameters and state into url
	 *
	 * @param $uri query uri
	 * @param $parameters additional query parameters
	 * @param $context encoded context or FALSE if none
	 * @return encoded url query
	 */
	public function encodeQuery($uri = '', $parameters = array(), $context = FALSE) {
		return $this->formatter->formatUrl($uri, $parameters);
	}

	/**
	 * Encode action into url
	 *
	 * @param $uri action uri
	 * @param $action action definition
	 * @param $context encoded context or FALSE if none
	 * @return encoded url query
	 */
	public function encodeAction($uri, $action, $context = FALSE) {
		return $this->formatter->formatUrl($uri, $action['parameters']);
	}


	/**
	 * Called to build a search query action
	 *
	 * @param $query textual query
	 * @return action
	 */
	public function buildQueryAction($query) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build a refine query term action
	 *
	 * @param $index term index
	 * @param $property optional property identifier
	 * @param $value optional value
	 * @return action
	 */
	public function buildRefineAction($index, $property = NULL, $value = NULL) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build a guidance remove action
	 *
	 * @param $property guidance property identifier
	 * @param $value optional value
	 * @return action
	 */
	public function buildGuidanceRemoveAction($property, $value = NULL) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build a scenario change action
	 *
	 * @param $scenario scenario identifier
	 * @return action
	 */
	public function buildScenarioAction($scenario) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build an add guidance attribute action
	 *
	 * @param $attribute guidance attribute
	 * @param $parents list of parent values
	 * @param $value value
	 * @return action
	 */
	public function buildAttributeAddAction($attribute, $parents, $value) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build a merge guidance attribute action
	 *
	 * @param $attribute guidance attribute
	 * @param $parents list of parent values
	 * @param $value value
	 * @return action
	 */
	public function buildAttributeMergeAction($attribute, $parents, $value) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build an set guidance attribute action
	 *
	 * @param $attribute guidance attribute
	 * @param $parents list of parent values
	 * @param $value value
	 * @return action
	 */
	public function buildAttributeSetAction($attribute, $parents, $value) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}

	/**
	 * Called to build a remove guidance attribute action
	 *
	 * @param $attribute guidance attribute
	 * @return action
	 */
	public function buildAttributeRemoveAction($attribute) {
		return array(
			'uri' => '',
			'parameters' => array()
		);
	}
}

/**
 * @}
 */

?>