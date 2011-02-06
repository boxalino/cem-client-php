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
 * Boxalino CEM Gateway client class
 *
 * @package cem
 * @subpackage client
 */
class CEM_GatewayClient {
	/**
	 * Gateway url
	 *
	 * @var string
	 */
	protected $url;

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
	 * Constructor
	 *
	 * @param string $url gateway url
	 * @param integer $connectionTimeout connection timeout (defaults to 10[s])
	 * @param integer $readTimeout read timeout (defaults to 15[s])
	 */
	public function __construct($url, $connectionTimeout = 10000, $readTimeout = 15000) {
		$this->url = $url;
		$this->connectionTimeout = $connectionTimeout;
		$this->readTimeout = $readTimeout;
	}


	/**
	 * Process gateway request/response interaction
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @param CEM_GatewayRequest &$request gateway request reference
	 * @param CEM_GatewayResponse &$response gateway response reference
	 * @return boolean TRUE on success or FALSE otherwise
	 */
	public function process(&$state, &$request, &$response) {
		// initialize curl
		$h = curl_init();
		if (!$h) {
			return FALSE;
		}

		// set curl url
		if (!curl_setopt($h, CURLOPT_URL,				$this->url)) {
			curl_close($h);
			return FALSE;
		}

		// set curl timeout
		if (!curl_setopt($h, CURLOPT_CONNECTTIMEOUT_MS,	$this->connectionTimeout)) {
			curl_close($h);
			return FALSE;
		}
		if (!curl_setopt($h, CURLOPT_TIMEOUT_MS,		$this->readTimeout)) {
			curl_close($h);
			return FALSE;
		}

		// set curl response options
		if (!curl_setopt($h, CURLOPT_RETURNTRANSFER,	TRUE)) {
			curl_close($h);
			return FALSE;
		}
		if (!curl_setopt($h, CURLOPT_HEADER,			FALSE)) {
			curl_close($h);
			return FALSE;
		}
		if (!curl_setopt($h, CURLOPT_HEADERFUNCTION,	array($this, 'parseHeader'))) {
			curl_close($h);
			return FALSE;
		}

		// set curl request body
		$requestData = $request->write($state);
		if ($requestData) {
			if (!curl_setopt($h, CURLOPT_HTTPHEADER, 	array('Content-Type: ' . $request->getContentType()))) {
				curl_close($h);
				return FALSE;
			}
			if (!curl_setopt($h, CURLOPT_POST,			TRUE)) {
				curl_close($h);
				return FALSE;
			}
			if (!curl_setopt($h, CURLOPT_POSTFIELDS, 	$requestData)) {
				curl_close($h);
				return FALSE;
			}
		}

		// set curl request cookie
		if (strlen($state->getCookies()) > 0) {
			if (!curl_setopt($h, CURLOPT_COOKIE,		$state->getCookies())) {
				curl_close($h);
				return FALSE;
			}
		}

		// execute curl request/response
		$stateId = $this->nextStateId++;
		$this->states[$stateId] = array(
			'h' => &$h,
			's' => &$state
		);

		$time = microtime(TRUE);
		$responseData = curl_exec($h);
		$response->setTime(microtime(TRUE) - $time);
		$code = curl_getinfo($h, CURLINFO_HTTP_CODE);
		$error = curl_error($h);

		unset($this->states[$stateId]);

		// close curl
		curl_close($h);

		// debug
//		echo($requestData."\n");
//		echo($responseData."\n");
//		echo("<pre>".htmlentities($requestData)."</pre>");
//		echo("<pre>".htmlentities($responseData)."</pre>");

		// parse response
		$state->setStatus($code, $error);
		if ($responseData && $code >= 200 && $code < 300) {
			return $response->read($state, $responseData);
		}
		return FALSE;
	}


	/**
	 * @ignore Active CURL states
	 *
	 * @var array
	 */
	private $states = array();

	/**
	 * @ignore Next CURL state id
	 *
	 * @var int
	 */
	private $nextStateId = 0;


	/**
	 * @ignore Called by CURL to parse http header
	 *
	 * @param object $h CURL handle
	 * @param string $data header line
	 * @return integer line size
	 */
	private function parseHeader($h, $data) {
		// find state
		$state = NULL;
		foreach ($this->states as $entry) {
			if ($entry['h'] === $h) {
				$state = $entry['s'];
				break;
			}
		}

		// parse cookies
		if ($state != NULL) {
			$cookieData = NULL;
			if (stripos($data, "Set-Cookie:") === 0) {
				$cookieData = trim(substr($data, strlen("Set-Cookie:")));
			} else if (stripos($data, "Set-Cookie2:") === 0) {
				$cookieData = trim(substr($data, strlen("Set-Cookie2:")));
			}
			if ($cookieData) {
				$parts = explode(';', $cookieData);
				if (count($parts) > 0) {
					$value = explode('=', $parts[0]);
					$cookie = array(
						'value' => $value[1],
						'parameters' => array()
					);
					for ($i = 1; $i < count($parts); $i++) {
						$parameter = explode('=', $parts[$i]);
						$cookie['parameters'][$parameter[0]] = $parameter[1];
					}
					$state->setCookie($value[0], $cookie);
				}
			}
		}
		return strlen($data);
	}
}

?>