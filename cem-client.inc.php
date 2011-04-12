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
 * Abstract gateway request
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_GatewayRequest {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get request body content-type
	 *
	 * @return request body content-type
	 */
	public abstract function getContentType();

	/**
	 * Called to write the request
	 *
	 * @param &$state client state reference
	 * @return request raw body
	 */
	public abstract function write(&$state);
}

/**
 * Abstract gateway response
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_GatewayResponse {
	/**
	 * Processing time
	 */
	protected $totalTime;	


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Get processing time
	 *
	 * @return processing time (in seconds)
	 */
	public function getTotalTime() {
		return $this->totalTime;
	}

	/**
	 * Called to set processing time
	 *
	 * @param $time processing time (in seconds)
	 */
	public function setTotalTime($time) {
		$this->totalTime = $time;
	}

	/**
	 * Called to read the response
	 *
	 * @param &$state client state reference
	 * @param &$data response raw body
	 * @return TRUE on success, FALSE otherwise
	 */
	public abstract function read(&$state, &$data);
}


/**
 * Boxalino CEM Gateway client class
 *
 * @author nitro@boxalino.com
 */
class CEM_GatewayClient {
	/**
	 * Gateway url
	 */
	protected $url;

	/**
	 * Connection timeout [ms]
	 */
	protected $connectionTimeout;

	/**
	 * Read timeout [ms]
	 */
	protected $readTimeout;


	/**
	 * Constructor
	 *
	 * @param $url gateway url
	 * @param $connectionTimeout connection timeout (defaults to 10[s])
	 * @param $readTimeout read timeout (defaults to 15[s])
	 */
	public function __construct($url, $connectionTimeout = 10000, $readTimeout = 15000) {
		$this->url = $url;
		$this->connectionTimeout = $connectionTimeout;
		$this->readTimeout = $readTimeout;
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
		$cookieHeader = $state->getCookieHeader();
		if (strlen($cookieHeader) > 0) {
			if (!curl_setopt($h, CURLOPT_COOKIE,		$cookieHeader)) {
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
		$response->setTotalTime(microtime(TRUE) - $time);
		$code = curl_getinfo($h, CURLINFO_HTTP_CODE);
		$error = curl_error($h);

		unset($this->states[$stateId]);

		// close curl
		curl_close($h);

		// debug
//		echo($requestData."\n".$responseData);
//		echo("<pre style=\"width: 100%; overflow: auto; background-color: white; color: black;\">" . htmlentities($requestData, ENT_COMPAT, 'UTF-8')."\n".htmlentities($responseData, ENT_COMPAT, 'UTF-8') . "</pre>");

		// parse response
		$state->setStatus($code, $error);
		if ($responseData && $code >= 200 && $code < 300) {
			return $response->read($state, $responseData);
		}
		return FALSE;
	}


	/**
	 * @internal Active CURL states
	 */
	private $states = array();

	/**
	 * @internal Next CURL state id
	 */
	private $nextStateId = 0;


	/**
	 * @internal Called by CURL to parse http header
	 *
	 * @param $h CURL handle
	 * @param $data header line
	 * @return line size
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
			if (stripos($data, "set-cookie:") === 0) {
				$cookieData = trim(substr($data, strlen("set-cookie:")));
			} else if (stripos($data, "set-cookie2:") === 0) {
				$cookieData = trim(substr($data, strlen("set-cookie2:")));
			}
			if ($cookieData) {
				$parts = explode(';', $cookieData);
				if (count($parts) > 0) {
					$value = explode('=', $parts[0]);
					$parameters = array();
					for ($i = 1; $i < count($parts); $i++) {
						$parameter = explode('=', $parts[$i]);
						$parameters[$parameter[0]] = $parameter[1];
					}
					$state->setCookie($value[0], $value[1], $parameters);
				}
			}
		}
		return strlen($data);
	}
}

/**
 * @}
 */

?>