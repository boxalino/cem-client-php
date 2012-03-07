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
 * Data formatter for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_WebFormatter {
	/**
	 * Locale
	 */
	private $locale;

	/**
	 * Locale currency
	 */
	private $localeCurrency;

	/**
	 * Locale currency pattern
	 */
	private $localeCurrencyPattern;

	/**
	 * Default url parameters
	 */
	private $defaultUrlParameters = array();

	/**
	 * Date/time formatters (cache)
	 */
	private $_dateFormatters = array();

	/**
	 * Number formatters (cache)
	 */
	private $_numberFormatters = array();

	/**
	 * Price formatter (cache)
	 */
	private $_priceFormatter = NULL;

	/**
	 * Price code (cache)
	 */
	private $_priceCode = NULL;


	/**
	 * Constructor
	 *
	 * @param $locale locale name
	 * @param $localeCurrency locale currency code
	 * @param $localeCurrencyPattern locale currency pattern
	 * @param $defaultUrlParameters default url parameters
	 */
	public function __construct($locale, $localeCurrency, $localeCurrencyPattern, $defaultUrlParameters = array()) {
		$this->locale = $locale;
		$this->localeCurrency = $localeCurrency;
		$this->localeCurrencyPattern = $localeCurrencyPattern;
		$this->defaultUrlParameters = $defaultUrlParameters;
	}


	/**
	 * Format filter's label
	 *
	 * @param $property filter property
	 * @param $guidance filter descriptor
	 * @return filter's label
	 */
	public function formatFilterValue($property, $guidance) {
		switch ($property['dataType']) {
		case 'date':
			$dateFormat = IntlDateFormatter::SHORT;
			$timeFormat = IntlDateFormatter::SHORT;
			if ($guidance->data[0] != '*') {
				$from = $this->formatDateTime(self::parseDateISO8601($guidance->data[0]), $dateFormat, $timeFormat);
			} else {
				$from = NULL;
			}
			if ($guidance->data[1] != '*') {
				$to = $this->formatDateTime(self::parseDateISO8601($guidance->data[1]), $dateFormat, $timeFormat);
			} else {
				$to = NULL;
			}
			if ($from === NULL) {
				return "< $to";
			} else if ($to === NULL) {
				return ">= $from";
			}
			return "$from - $to";

		case 'number':
			if ($guidance->data[0] != '*') {
				if (stripos($property['id'], 'price') !== FALSE) {
					$from = $this->formatPrice($guidance->data[0]);
				} else if (round($guidance->data[0]) != $guidance->data[0]) {
					$from = $this->formatFloat($guidance->data[0]);
				} else {
					$from = $this->formatInteger($guidance->data[0]);
				}
			} else {
				$from = NULL;
			}
			if ($guidance->data[1] != '*') {
				if (stripos($property['id'], 'price') !== FALSE) {
					$to = $this->formatPrice($guidance->data[1]);
				} else if (round($guidance->data[1]) != $guidance->data[1]) {
					$to = $this->formatFloat($guidance->data[1]);
				} else {
					$to = $this->formatInteger($guidance->data[1]);
				}
			} else {
				$to = NULL;
			}
			if ($from === NULL) {
				return "< $to";
			} else if ($to === NULL) {
				return ">= $from";
			}
			return "$from - $to";
		}
		return end($guidance->data);
	}

	/**
	 * Format attribute value's label
	 *
	 * @param $attribute attribute descriptor
	 * @param $index value index
	 * @param $value value descriptor
	 * @return value's label
	 */
	public function formatAttributeValue($attribute, $index, $value) {
		switch ($attribute->type) {
		case 'dateRange':
			$dateFormat = IntlDateFormatter::SHORT;
			switch ($attribute->data[0]) {
			case 'year':
			case 'semester':
			case 'quarter':
			case 'month':
			case 'week':
			case 'day':
				$timeFormat = IntlDateFormatter::NONE;
				break;

			default:
				$timeFormat = IntlDateFormatter::SHORT;
				break;
			}
			if ($value->data[0] != '*') {
				$from = $this->formatDateTime(self::parseDateISO8601($value->data[0]), $dateFormat, $timeFormat);
			} else {
				$from = NULL;
			}
			if ($value->data[1] != '*') {
				$to = $this->formatDateTime(self::parseDateISO8601($value->data[1]), $dateFormat, $timeFormat);
			} else {
				$to = NULL;
			}
			if ($from === NULL || ($index == 0 && $to !== NULL)) {
				return "< $to";
			} else if ($to === NULL || ($index >= (sizeof($attribute->values) - 1) && $from !== NULL)) {
				return ">= $from";
			}
			return "$from - $to";

		case 'numberRange':
			$range = floatval($attribute->data[0]);
			if ($value->data[0] != '*') {
				if (stripos($attribute->property, 'price') !== FALSE) {
					$from = $this->formatPrice($value->data[0]);
				} else if (round($range) != $range) {
					$from = $this->formatFloat($value->data[0]);
				} else {
					$from = $this->formatInteger($value->data[0]);
				}
			} else {
				$from = NULL;
			}
			if ($value->data[1] != '*') {
				if (stripos($attribute->property, 'price') !== FALSE) {
					$to = $this->formatPrice($value->data[1]);
				} else if (round($range) != $range) {
					$to = $this->formatFloat($value->data[1]);
				} else {
					$to = $this->formatInteger($value->data[1]);
				}
			} else {
				$to = NULL;
			}
			if ($from === NULL || ($index == 0 && $to !== NULL)) {
				return "< $to";
			} else if ($to === NULL || ($index >= (sizeof($attribute->values) - 1) && $from !== NULL)) {
				return ">= $from";
			}
			return "$from - $to";
		}
		return implode(', ', $value->data);
	}


	/**
	 * Get complete url parameters
	 *
	 * @param $parameters query parameters
	 * @return full parameters
	 */
	public function getUrlParameters($parameters) {
		$list = array();
		foreach ($this->defaultUrlParameters as $k => $v) {
			$list[$k] = $v;
		}
		foreach ($parameters as $k => $v) {
			$list[$k] = $v;
		}
		return $list;
	}

	/**
	 * Format url with parameters
	 *
	 * @param $uri base uri
	 * @param $parameters query parameters
	 * @param $fragment fragment
	 * @return full uri
	 */
	public function formatUrl($uri, $parameters = array(), $fragment = NULL) {
		return CEM_HttpClient::buildUrl($uri, $this->getUrlParameters($parameters), $fragment);
	}


	/**
	 * Format date value
	 *
	 * @param $value date in ISO8601 format
	 * @param $dateFormat date format
	 * @return formatted date
	 */
	public function formatDate($value, $dateFormat = IntlDateFormatter::LONG) {
		return $this->formatDateTime($value, $dateFormat, IntlDateFormatter::NONE);
	}

	/**
	 * Format date/time value
	 *
	 * @param $value date/time in ISO8601 format
	 * @param $dateFormat date format
	 * @param $timeFormat time format
	 * @return formatted date/time
	 */
	public function formatDateTime($value, $dateFormat = IntlDateFormatter::LONG, $timeFormat = IntlDateFormatter::SHORT) {
		if (!isset($this->_dateFormatters[$dateFormat]) ||
			!isset($this->_dateFormatters[$dateFormat][$timeFormat])) {
			$this->_dateFormatters[$dateFormat][$timeFormat] = IntlDateFormatter::create($this->locale, $dateFormat, $timeFormat);
		}
		return $this->_dateFormatters[$dateFormat][$timeFormat]->format(self::parseDateISO8601($value));
	}


	/**
	 * Format number value as integer
	 *
	 * @param $value number
	 * @return formatted number
	 */
	public function formatInteger($value) {
		return $this->formatNumber($value, NumberFormatter::DECIMAL, NumberFormatter::TYPE_INT64);
	}

	/**
	 * Format number value as float
	 *
	 * @param $value number
	 * @return formatted number
	 */
	public function formatFloat($value) {
		return $this->formatNumber($value, NumberFormatter::DECIMAL, NumberFormatter::TYPE_DOUBLE);
	}

	/**
	 * Format number value as percents
	 *
	 * @param $value number
	 * @return formatted number
	 */
	public function formatPercent($value) {
		return $this->formatNumber($value, NumberFormatter::PERCENT, NumberFormatter::TYPE_DOUBLE);
	}

	/**
	 * Format number value
	 *
	 * @param $value number
	 * @param $format number format
	 * @param $type number type
	 * @return formatted number
	 */
	public function formatNumber($value, $format = NumberFormatter::DECIMAL, $type = NumberFormatter::TYPE_DOUBLE) {
		if (!isset($this->_numberFormatters[$format])) {
			$this->_numberFormatters[$format] = NumberFormatter::create($this->locale, $format);
		}
		return $this->_numberFormatters[$format]->format($value, $type);
	}


	/**
	 * Format price value
	 *
	 * @param $value price
	 * @param $rounding round value
	 * @return formatted price
	 */
	public function formatPrice($value, $rounding = 0) {
		if ($this->_priceFormatter == NULL) {
			$this->_priceFormatter = NumberFormatter::create($this->locale, NumberFormatter::CURRENCY);
			if ($this->localeCurrency) {
				$this->_priceCode = $this->localeCurrency;
			} else {
				$this->_priceCode = $this->_priceFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
			}
			if ($this->localeCurrencyPattern) {
				$this->_priceFormatter->setPattern($this->localeCurrencyPattern);
			}
		}
		if ($rounding > 0) {
			$rounding = 1.0 / $rounding;
			$value = round($value * $rounding) / $rounding;
		}
		return $this->_priceFormatter->formatCurrency($value, $this->_priceCode);
	}


	/**
	 * Parse ISO8601 date format
	 *
	 * @param $value date string
	 * @return gmt timestamp
	 */
	public static function parseDateISO8601($value) {
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}).(\d{3})([+\-])(\d{2})(\d{2})$/', $value, $matches)) {
			$delta = intval($matches[9]) * 60 * 60 + intval($matches[10]) * 60;
			if ($matches[8] == '-') {
				$delta = -$delta;
			}
			return (mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]) + $delta);
		}
		return NULL;
	}


	/**
	 * Print a json variable for debug.
	 *
	 * @param $name block name
	 * @param $object json object
	 * @param $visibleDepth initial visible depth
	 */
	public static function printJsonBlock($name, $object, $visibleDepth = 0) {
		echo('<div id="cem-debug" class="cem-debug-block cem-debug-spacer"><h1>Debug: '.$name.' (');
		printf("%.02f [kb]", strlen(json_encode($object)) / 1024);
		echo(')</h1><div class="json-visible">');
		self::printJsonObject($object, $visibleDepth);
		echo('</div></div>');
	}

	/**
	 * Print a cem scope for debug.
	 *
	 * @param $name block name
	 * @param $scope cem scope
	 * @param $visibleDepth initial visible depth
	 */
	public static function printJsonScope($name, $scope, $visibleDepth = 0) {
		$level = $scope['level'];
		$mode = $scope['mode'];
		$size = strlen($scope['data']);
		$object = json_decode($scope['data']);
		if ($object && sizeof(get_object_vars($object)) > 0) {
			self::printJsonBlock($level.'::'.$mode.'::'.$name, $object, $visibleDepth);
		}
	}

	/**
	 * Print a json array for debug.
	 *
	 * @param $array json array
	 * @param $visibleDepth initial visible depth
	 * @param $depth current depth
	 */
	public static function printJsonArray($array, $visibleDepth = 0, $depth = 0) {
		for ($i = 0; $i < sizeof($array); $i++) {
			echo('<div class="json-array">');
			self::printJsonValue("$i.", $array[$i], $visibleDepth, $depth);
			echo('</div>');
		}
	}

	/**
	 * Print a json object for debug.
	 *
	 * @param $object json object
	 * @param $visibleDepth initial visible depth
	 * @param $depth current depth
	 */
	public static function printJsonObject($object, $visibleDepth = 0, $depth = 0) {
		foreach ($object as $key => $value) {
			echo('<div class="json-object">');
			self::printJsonValue($key, $value, $visibleDepth, $depth);
			echo('</div>');
		}
	}

	/**
	 * Print a json value for debug.
	 *
	 * @param $key current key
	 * @param $value json value
	 * @param $visibleDepth initial visible depth
	 * @param $depth current depth
	 */
	public static function printJsonValue($key, $value, $visibleDepth = 0, $depth = 0) {
		$visible = $depth < $visibleDepth;
		if (is_array($value)) {
			if (sizeof($value) > 0) {
				echo('<label><a href="#">'.($visible ? '[-]' : '[+]').' '.$key.'</a></label> (array: ['.sizeof($value).'])');
				echo('<div class="'.($visible ? 'json-visible' : 'json-hidden').'">');
				self::printJsonArray($value, $visibleDepth, $depth + 1);
				echo('</div>');
			} else {
				echo('<label>[ ] '.$key.'</label> (array)');
			}
		} else if (is_object($value)) {
			if (sizeof(get_object_vars($value)) > 0) {
				echo('<label><a href="#">'.($visible ? '[-]' : '[+]').' '.$key.'</a></label> (object: '.implode(', ', array_keys(get_object_vars($value))).')');
				echo('<div class="'.($visible ? 'json-visible' : 'json-hidden').'">');
				self::printJsonObject($value, $visibleDepth, $depth + 1);
				echo('</div>');
			} else {
				echo('<label>[ ] '.$key.'</label> (object)');
			}
		} else if (is_numeric($value)) {
			echo('<label>'.$key.'</label> (number): '.$value);
		} else if (is_bool($value)) {
			echo('<label>'.$key.'</label> (boolean): '.($value ? 'true' : 'false'));
		} else {
			echo(
				'<label>'.$key.'</label> (string): "'.
				str_replace(
					array(' ', "\t", "\n", "\r"),
					array('&nbsp;', '\\t', '\\n', '\\r'),
					htmlentities(addcslashes($value, '"'), ENT_COMPAT, 'UTF-8')
				).
				'"'
			);
		}
	}
}

/**
 * @}
 */

?>