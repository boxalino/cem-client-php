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
 * GS interaction for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_GS_Interaction extends CEM_AbstractWebHandler {
	/**
	 * Current request
	 */
	protected $request;

	/**
	 * Current response
	 */
	protected $response;

	/**
	 * User-defined options
	 */
	protected $options;

	/**
	 * Json decoded contexts
	 */
	protected $jsonContexts = array();

	/**
	 * Sequential context cache
	 */
	protected $sequentialContexts = NULL;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param &$request client request reference
	 * @param &$response client response reference
	 * @param &$options user-defined options
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
	 * @return underlying request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Get underlying response
	 *
	 * @return underlying response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get user-defined options
	 *
	 * @return user-defined options
	 */
	public function getOptions() {
		return $this->options;
	}


	/**
	 * Get server version
	 *
	 * @return server version
	 */
	public function getVersion() {
		return $this->response->getVersion();
	}

	/**
	 * Get status
	 *
	 * @return status
	 */
	public function getStatus() {
		return $this->response->getStatus();
	}

	/**
	 * Get response message
	 *
	 * @return response message
	 */
	public function getMessage() {
		return $this->response->getMessage();
	}

	/**
	 * Get time
	 *
	 * @return time (in seconds)
	 */
	public function getTime() {
		return $this->response->getTime();
	}


	/**
	 * Get context scopes
	 *
	 * @return context scopes
	 */
	public function getContext() {
		return $this->response->getContext();
	}

	/**
	 * Get context data
	 *
	 * @param $name context name
	 * @return context data
	 */
	public function getContextData($name) {
		$scopes = $this->getContext();
		if (isset($scopes[$name])) {
			return $scopes[$name]['data'];
		}
		return '';
	}

	/**
	 * Get context data from json
	 *
	 * @param $name context name
	 * @return context data (decoded)
	 */
	public function getContextJson($name) {
		if (!isset($this->jsonContexts[$name])) {
			$this->jsonContexts[$name] = @json_decode($this->getContextData($name));
		}
		return $this->jsonContexts[$name];
	}


	/**
	 * Encode sequential contexts
	 *
	 * @param $contexts optional custom contexts
	 * @return encoded sequential contexts
	 */
	public function encodeSequentialContexts($contexts = NULL) {
		if ($this->sequentialContexts == NULL) {
			$data = '';
			if ($contexts == NULL) {
				$contexts = $this->response->getContext();
			}
			foreach ($contexts as $name => $scope) {
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
			$this->sequentialContexts = $this->encrypt($data);
		}
		return $this->sequentialContexts;
	}

	/**
	 * Encode parameters and state into url
	 *
	 * @param $parameters additional query parameters
	 * @return encoded url query
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
	 * Check if group exists
	 *
	 * @param $id group identifier
	 * @return TRUE if group exists or FALSE if none
	 */
	public function hasGroup() {
		$scopes = $this->response->getResponses();

		return isset($scopes[$id]);
	}

	/**
	 * Get group
	 *
	 * @param $id group identifier
	 * @return group or NULL if none
	 */
	public function getGroup($id = 'search') {
		$scopes = $this->response->getResponses();
		if (isset($scopes[$id])) {
			return $scopes[$id];
		}
		return NULL;
	}

	/**
	 * Get groups
	 *
	 * @return group scopes
	 */
	public function getGroups() {
		return $this->response->getResponses();
	}
}

/**
 * @}
 */

?>