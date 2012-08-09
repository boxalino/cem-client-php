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
 * Boxalino CEM Gateway client class
 *
 * @author nitro@boxalino.com
 */
class CEM_GatewayClient extends CEM_HttpClient {
	/**
	 * Constructor
	 *
	 * @param $connectionTimeout connection timeout (defaults to 2[s])
	 * @param $readTimeout read timeout (defaults to 15[s])
	 */
	public function __construct($connectionTimeout = 2000, $readTimeout = 15000) {
		parent::__construct(FALSE, FALSE, $connectionTimeout, $readTimeout);
	}


	/**
	 * Process gateway request/response interaction
	 *
	 * @param $url gateway url
	 * @param $state client state reference
	 * @param $request gateway request reference
	 * @param $response gateway response reference
	 * @return TRUE on success or FALSE otherwise
	 */
	public function exec($url, $state, $request, $response) {
		// forward cookies
		foreach ($state->getCookies() as $cookie) {
			$this->setCookie($cookie['name'], $cookie['value']);
		}

		// perform request
		$requestBody = $request->write($state);
		if (is_array($requestBody)) {
			$this->postFields($url, $requestBody, $request->getContentCharset(), $request->getReferer());
		} else if ($requestBody) {
			$this->post($url, $request->getContentType(), $requestBody, $request->getReferer());
		} else {
			$this->get($url, array(), $request->getReferer());
		}

		// forward cookies
		foreach ($this->getCookies() as $name => $cookie) {
			$state->setCookie($name, $cookie);
		}

		// parse response
		$response->setTransport($this->getCode(), $this->getError(), $this->getTime(), $this->getBody());
		if ($this->getBody() && $this->getCode() >= 200 && $this->getCode() < 300) {
			return $response->read($state, $this->getBody());
		}
		return FALSE;
	}


	/**
	 * Set request's target cluster
	 *
	 * @param $cluster cluster identifier (NULL to remove)
	 */
	public function setRequestCluster($cluster) {
		if ($cluster) {
			$this->setRequestHeader('X-Cem-Cluster', $cluster);
		} else {
			$this->removeRequestHeader('X-Cem-Cluster');
		}
	}
}

/**
 * @}
 */

?>