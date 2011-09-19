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
 * PR interaction for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_Interaction extends CEM_AbstractWebHandler {
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
	 * Value formatter
	 */
	protected $formatter;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param &$request client request reference
	 * @param &$response client response reference
	 * @param &$formatter value formatter
	 */
	public function __construct(&$crypto, &$request, &$response, &$options, &$formatter) {
		parent::__construct($crypto);
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
		$this->formatter = $formatter;
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
	 * Get value formatter
	 *
	 * @return value formatter
	 */
	public function getFormatter() {
		return $this->formatter;
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
	 * Get recommendation
	 *
	 * @param $index recommendation index
	 * @return recommendation or FALSE if none
	 */
	public function getRecommendation($index = 0) {
		$responses = $this->response->getResponses();
		if ($index >= 0 && $index < sizeof($responses)) {
			return $responses[$index];
		}
		return FALSE;
	}

	/**
	 * Get recommendations
	 *
	 * @return recommendations
	 */
	public function getRecommendations() {
		return $this->response->getResponses();
	}


	/**
	 * Build resource descriptor
	 *
	 * @param $resource resource
	 * @return resource descriptor
	 */
	public function buildResource($resource) {
		$data = array(
			'id' => $resource->id,
			'type' => $resource->type,
			'language' => $resource->language,
			'name' => $resource->name,
			'properties' => array(),
			'weight' => isset($resource->weight) ? $resource->weight : 0
		);
		$properties = array();
		foreach ($resource->properties as $value) {
			$list = $this->collapsePropertyValue($value);
			if (!isset($data[$value->property])) {
				$data[$value->property] = sizeof($list) > 1 ? $list : $list[0];
			}
			$data['properties'][$value->property][] = sizeof($list) > 1 ? $list : $list[0];
		}
		return $data;
	}


	/**
	 * Collect hierarchical values.
	 *
	 * @param $value current node
	 * @return property values
	 */
	private function collapsePropertyValue($value) {
		$list = array();
		$list[] = $value->value;
		if (isset($value->child)) {
			$list = array_merge($list, $this->collapsePropertyValue($value->child));
		}
		return $list;
	}
}

/**
 * @}
 */

?>