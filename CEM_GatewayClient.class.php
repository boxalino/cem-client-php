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
 * Boxalino CEM Gateway client class
 *
 * @author nitro@boxalino.com
 */
class CEM_GatewayClient {
	/**
	 * @internal Gateway url
	 */
	protected $url;

	/**
	 * @internal Http client
	 */
	protected $client;


	/**
	 * Constructor
	 *
	 * @param $url gateway url
	 * @param $connectionTimeout connection timeout (defaults to 10[s])
	 * @param $readTimeout read timeout (defaults to 15[s])
	 */
	public function __construct($url, $connectionTimeout = 10000, $readTimeout = 15000) {
		$this->url = $url;
		$this->client = new CEM_HttpClient(FALSE, FALSE, $connectionTimeout, $readTimeout);
	}


	/**
	 * Process gateway request/response interaction
	 *
	 * @param &$state client state reference
	 * @param &$request gateway request reference
	 * @param &$response gateway response reference
	 * @return TRUE on success or FALSE otherwise
	 */
	public function process(&$state, &$request, &$response) {
		// forward cookies
		foreach ($state->getCookies() as $cookie) {
			$this->client->setCookie($cookie['name'], $cookie['value']);
		}

		// perform request
		$requestBody = $request->write($state);
		if ($requestBody) {
			$this->client->post(
				$this->url,
				$request->getContentType(),
				$requestBody,
				$request->getReferer()
			);
		} else {
			$this->client->get(
				$this->url,
				array(),
				$request->getReferer()
			);
		}

		// forward cookies
		foreach ($this->client->getCookies() as $name => $cookie) {
			$state->setCookie($name, $cookie);
		}

		// debug
/*		echo('<pre>');
		print_r($this->client);
		echo($requestBody."\n".$this->client->getBody());
		echo('</pre>');
		echo("<pre style=\"width: 100%; overflow: auto; background-color: white; color: black;\">" . htmlentities($requestData, ENT_COMPAT, 'UTF-8')."\n".htmlentities($responseData, ENT_COMPAT, 'UTF-8') . "</pre>");
		exit;*/

		// parse response
		$state->setStatus($this->client->getCode(), $this->client->getError());
		$response->setTotalTime($this->client->getTime());
		$responseBody = $this->client->getBody();
		if ($responseBody && $this->client->getCode() >= 200 && $this->client->getCode() < 300) {
			return $response->read($state, $responseBody);
		}
		return FALSE;
	}
}

/**
 * @}
 */

?>