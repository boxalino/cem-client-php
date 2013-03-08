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
 * PR interaction for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_Interaction {
	/**
	 * Action encoder
	 */
	protected $encoder;

	/**
	 * Value formatter
	 */
	protected $formatter;

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
	 * Constructor
	 *
	 * @param $encoder action encoder
	 * @param $formatter value formatter
	 * @param $request client request reference
	 * @param $response client response reference
	 * @param $formatter value formatter
	 */
	public function __construct($encoder, $formatter, $request, $response, $options) {
		$this->encoder = $encoder;
		$this->formatter = $formatter;
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
	}


	/**
	 * Get action encoder
	 *
	 * @return action encoder
	 */
	public function getEncoder() {
		return $this->encoder;
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
	 * Get remote time
	 *
	 * @return remote time (in seconds)
	 */
	public function getTime() {
		return $this->response->getTime();
	}

	/**
	 * Get processing time
	 *
	 * @return processing time (in seconds)
	 */
	public function getTotalTime() {
		return $this->response->getTotalTime();
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