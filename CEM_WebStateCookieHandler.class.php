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
 * Cookie-based CEM state handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebStateCookieHandler extends CEM_WebStateHandler {
	/**
	 * Cookie prefix
	 */
	protected $prefix;

	/**
	 * Path
	 */
	protected $path;

	/**
	 * Domain
	 */
	protected $domain;

	/**
	 * Secure flag
	 */
	protected $secure;

	/**
	 * Visitor expiry time
	 */
	protected $expiry;

	/**
	 * Chunk length
	 */
	protected $chunkLength;


	/**
	 * Constructor
	 *
	 * @param $crypto encryption facility
	 * @param $prefix cookie prefix (defaults to 'cem')
	 * @param $path path (defaults to '/')
	 * @param $domain domain (defaults to any)
	 * @param $secure secure flag (defaults to FALSE)
	 * @param $expiry visitor expiry time in seconds (defaults to 30 days)
	 * @param $chunkLength cookie chunk size (defaults to 4095 bytes)
	 */
	public function __construct($crypto, $prefix = 'cem', $path = '/', $domain = FALSE, $secure = FALSE, $expiry = 2592000, $chunkLength = 4095) {
		parent::__construct($crypto);
		$this->prefix = $prefix;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->expiry = $expiry;
		$this->chunkLength = $chunkLength;

		// parse cem, levels, state data cookies
		$cem = $this->readCookies($this->prefix.'a');
		$visitor = $this->readCookies($this->prefix.'b');
		$session = $this->readCookies($this->prefix.'c');
		$search = $this->readCookies($this->prefix.'d');
		$data = $this->readCookies($this->prefix.'e');
		if ($cem != null || $visitor != NULL || $session != NULL || $search != NULL || $data != NULL) {
			$this->state = new CEM_GatewayState();

			// decode cem client cookies
			if (strlen($cem) > 0) {
				foreach (explode(';', $cem) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$this->state->setCookie(urldecode($name), urldecode($value));
					}
				}
			}

			// decode context levels
			$context = array();
			if (strlen($visitor) > 0) {
				foreach (explode(';', $visitor) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[$this->unescapeValue($name)] = array(
							'level' => 'visitor',
							'mode' => 'aggregate',
							'data' => $this->unescapeValue($value)
						);
					}
				}
			}
			if (strlen($session) > 0) {
				foreach (explode(';', $session) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[$this->unescapeValue($name)] = array(
							'level' => 'session',
							'mode' => 'aggregate',
							'data' => $this->unescapeValue($value)
						);
					}
				}
			}
			if (strlen($search) > 0) {
				foreach (explode(';', $search) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$context[$this->unescapeValue($name)] = array(
							'level' => 'search',
							'mode' => 'aggregate',
							'data' => $this->unescapeValue($value)
						);
					}
				}
			}
			$this->state->set('context', $context);

			// decode other state data
			if (strlen($data) > 0) {
				foreach (explode(';', $data) as $item) {
					list($name, $value) = explode('=', $item);

					if (strlen($name) > 0) {
						$this->state->set(
							$this->unescapeValue($name),
							json_decode($this->unescapeValue($value))
						);
					}
				}
			}
		}
	}


	/**
	 * Write client state to storage
	 *
	 * @param $state client state
	 */
	public function write($state) {
		// write cem cookies
		$this->writeCookies($this->prefix.'a', $state->getCookieHeader(), FALSE);

		// write levels cookies
		$context = $state->get('context', array());
		$visitor = '';
		$session = '';
		$search = '';
		foreach ($context as $key => $item) {
			if ($item['mode'] == 'aggregate') {
				switch ($item['level']) {
				case 'visitor':
					if (strlen($visitor) > 0) {
						$visitor .= ';';
					}
					$visitor .= $this->escapeValue($key) . '=' . $this->escapeValue($item['data']);
					break;

				case 'session':
					if (strlen($session) > 0) {
						$session .= ';';
					}
					$session .= $this->escapeValue($key) . '=' . $this->escapeValue($item['data']);
					break;

				case 'search':
					if (strlen($search) > 0) {
						$search .= ';';
					}
					$search .= $this->escapeValue($key) . '=' . $this->escapeValue($item['data']);
					break;
				}
			}
		}
		$this->writeCookies($this->prefix.'b', $visitor, TRUE);
		$this->writeCookies($this->prefix.'c', $session, FALSE);
		$this->writeCookies($this->prefix.'d', $search, FALSE);

		// write state data cookies
		$data = '';
		foreach ($state->getAll() as $key => $value) {
			if ($key != 'context') {
				if (strlen($data) > 0) {
					$data .= ';';
				}
				$data .= $this->escapeValue($key) . '=' . $this->escapeValue(json_encode($value));
			}
		}
		$this->writeCookies($this->prefix.'e', $data, FALSE);

		parent::write($state);
	}

	/**
	 * Remove client state from storage
	 *
	 * @param $state client state
	 */
	public function remove($state) {
		// clear cem, levels, state data cookies
		$this->writeCookies($this->prefix.'a');
		$this->writeCookies($this->prefix.'b');
		$this->writeCookies($this->prefix.'c');
		$this->writeCookies($this->prefix.'d');
		$this->writeCookies($this->prefix.'e');

		parent::remove($state);
	}


	/**
	 * Read cookie sequence
	 *
	 * @param $prefix cookie prefix
	 * @return plain cookie data
	 */
	protected function readCookies($prefix) {
		$i = 0;
		$data = '';
		while (isset($_COOKIE[$prefix.$i])) {
			$data .= $_COOKIE[$prefix.$i];
			$i++;
		}
		return $this->decrypt($data);
	}

	/**
	 * Write cookie sequence
	 *
	 * @param $prefix cookie prefix
	 * @param $data plain cookie data
	 * @param $visitor visitor if true, session if false
	 */
	protected function writeCookies($prefix, $data = '', $visitor = FALSE) {
		$i = 0;
		$data = $this->encrypt($data);
		if ($data) {
			$offset = 0;
			while ($offset < strlen($data)) {
				$name = $prefix.$i;
				$chunkLength = $this->chunkLength - strlen($name) - 1;
				if (($offset + $chunkLength) < strlen($data)) {
					$chunk = substr($data, $offset, $chunkLength);
				} else {
					$chunk = substr($data, $offset);
				}
				setcookie(
					$name,
					$chunk,
					$visitor ? time() + $this->expiry : 0,
					$this->path,
					$this->domain,
					$this->secure
				);
				$offset += strlen($chunk);
				$i++;
			}
		}
		while (isset($_COOKIE[$prefix.$i]) && strlen($_COOKIE[$prefix.$i]) > 0) {
			setcookie($prefix.$i, '', time() - (24 * 60 * 60), $this->path, $this->domain, $this->secure);
			$i++;
		}
	}
}

/**
 * @}
 */

?>