<?php

namespace Smoke;

/**
 * Static class for validating variables
 *
 * @author Peter Donders
 * @version 1.9.0
 *
 * Changelog
 * 1.9.0
 * 		Added isUTF8String method
 * 		Changed isString method: now no longer checks for is_string, but checks against is_array, is_object and is_null
 * 1.8.0
 * 		Added isBSN method
 * 		Added isDutchLicensePlate method
 * 1.7.1
 * 		Changed isFloat method: now uses a regex check instead of buggy typecasting check
 * 1.7.0
 * 		Added isJson function
 * 		Added isJavascriptFunction method
 * 1.6.0
 * 		Added isIBAN method
 * 1.5.2
 * 		Changed isURL method: spaces are now a valid part of the path and params
 * 1.5.1
 * 		Changed isURL method: bugfix on domain check
 * 1.5.0
 * 		Changed isEmail method: now supports multiple e-mail addresses
 * 		Changed isContent method: stand-alone iframe is now a valid type of content
 * 		Changed isURL method: now uses isDomain method
 * 		Changed isDomain method: added check for IP address and updated regex for new TLDs
 * 		Added isIP method
 * 1.4.7
 * 		Changed isURL method: now supports IPv4 addresses as domain
 * 1.4.6
 * 		Changed isURL method: now supports / in query params to support media module redirects
 * 1.4.5
 * 		Changed isURL method: now supports "localhost" as domain
 * 		Changed isDomain method: added support for four-character domain extensions (.mobi)
 * 1.4.4
 *		Changed isNaturalTime method: first numberic can now also be 1 digit
 * 1.4.3
 * 		Changed isDomain method: _ is now allowed in domain
 * 1.4.2
 * 		Changed isURL method: * is now allowed in query string
 * 1.4.1
 *		Changed isContent method: input-tags now also validate as content
 * 		Changed isURL method: local addresses (*.localhost) are now allowed
 * 1.4.0
 * 		Added TIME_HHMM constant
 * 		Added TIME_HHMMSS constant
 * 		Added isNaturalTime method
 * 		Changed isFloat method: bugfixes on checks
 * 		Changed isURL method: + is now allowed in query
 * 		Changed isContent method: booleans are not content
 * 1.3.8
 * 		Changed isURL method: [ and ] are now allowed in query
 * 1.3.7
 * 		Changed isFloat method: bugfixes on decimal point char
 * 1.3.6
 * 		Changed isValidReferer method: now uses a parameter
 * 1.3.5
 * 		Changed isContent method: object, embed and script tags are considered content now
 * 1.3.4
 * 		Changed isURL method: . is now allowed in query
 * 1.3.3
 * 		Changed isURL method: % is now allowed in path
 * 		Changed isURL method: %, - and _ are now allowed in query
 * 1.3.2
 * 		Changed all eregi calls to preg_match
 * 1.3.1
 * 		Changed isFloat method: improved algoritm
 * 1.3.0
 * 		Changed isColor method: added allowedEmpty parameter
 * 1.2.2
 * 		Changed isEnum method: improved algoritm
 * 1.2.1
 * 		Changed isURL method: bugfixes on notices
 * 1.2.0
 * 		Added isBlank method
 * 		Added isJavascriptVar method
 * 1.1.0
 * 		Added DATE_DDMMYYYY constant
 * 		Added DATE_MMDDYYYY constant
 * 		Added DATE_YYYYMMDD constant
 * 		Added isDate method
 * 		Added isUsername Method
 * 1.0.2
 * 		Added isCaptchaCorrect method
 * 1.0.1
 * 		Changed isError method: calls to functions from previous framework replaced
 * 1.0.0
 * 		First version
 */
class Validator
{
	const ZIPCODE_ALL		= 0;
	const ZIPCODE_NUMBERS	= 1;
	const ZIPCODE_CHARS		= 2;

	const DATE_DDMMYYYY 	= 1;
	const DATE_MMDDYYYY 	= 2;
	const DATE_YYYYMMDD 	= 3;

	const TIME_HHMM			= 1;
	const TIME_HHMMSS		= 2;

	/**
	 * Private constructor to force this class to be static
	 *
	 */
	private function __construct()
	{

	}

	/*** Logical variables ***/
	/**
	 * Check if the variable is a boolean
	 *
	 * @param mixed $bool
	 * @return bool
	 */
	public static function isBool($bool)
	{
		if (isset($bool) && is_bool($bool))
			return true;
		else
			return false;
	}

	/**
	 * Check if the variable is a valid error reply
	 * (boolean false or an int lower than 0)
	 *
	 * @deprecated
	 * @param mixed $error
	 * @return bool
	 */
	public static function isError($error)
	{
		if ((self::isBool($error) && $error == false) || (self::isInt($error) && $error < 0))
			return true;
		else
			return false;
	}

	/*** Numberic variables ***/
	/**
	 * Check if the variable is a valid integer number
	 *
	 * @param mixed $int
	 * @return bool
	 */
	public static function isInt($int)
	{
		if (isset($int) && is_numeric($int) && ((int) $int) == $int)
			return true;
		else
			return false;
	}

	/**
	 * Check if the variable is a valid floating point number
	 *
	 * @param mixed $float
	 * @return bool
	 */
	public static function isFloat($float)
	{
		return preg_match('/^\d+([,.]\d*)?$/', $float);
	}

	/*** Text variables ***/
	/**
	 * Check if a variable is a valid string
	 *
	 * @param mixed $string
	 * @param int $minLength Minimum length of the string (false or 0 if not applicable)
	 * @param int $maxLength Maximum length of the string (false of 0 if not applicable)
	 * @param bool $ignoreWhiteSpaces Trim whitespaces before checking the length of the string
	 * @return bool
	 */
	public static function isString($string, $minLength = 1, $maxLength = false, $ignoreWhiteSpaces = true)
	{
		if (!isset($string) || is_object($string) || is_null($string) || is_array($string))
			return false;

		if ($ignoreWhiteSpaces)
			$string = trim($string);

		if (self::isInt($minLength) && $minLength > 0 && strlen($string) < $minLength)
			return false;

		if (self::isInt($maxLength) && $maxLength > 0 && strlen($string) > $maxLength)
			return false;

		return true;
	}

	/**
	 * Check if a string has a UTF-8 encoding
	 *
	 * @since 1.9.0
	 * @see http://php.net/manual/en/function.mb-check-encoding.php
	 * @param string $string
	 * @return boolean
	 */
	public function isUTF8String($string, $minLength = 1, $maxLength = false, $ignoreWhiteSpaces = true)
	{
		if (!Validator::isString($string, $minLength, $maxLength, $ignoreWhiteSpaces))
			return false;

		$len = strlen($string);

		for ($i = 0; $i < $len; $i++)
		{
			$c = ord($string[$i]);
			if ($c > 128)
			{
				if($c > 247)
					return false;
				elseif ($c > 239)
					$bytes = 4;
				elseif ($c > 223)
					$bytes = 3;
				elseif ($c > 191)
					$bytes = 2;
				else
					return false;

				if (($i + $bytes) > $len)
					return false;

				while ($bytes > 1)
				{
					$i++;
					$b = ord($str[$i]);
					if ($b < 128 || $b > 191)
						return false;
					$bytes--;
				}
			}
		}

		return true;
	}

	/**
	 * Check if a variable is a valid URL
	 *
	 * @param mixed $url
	 * @return bool
	 */
	public static function isURL($url)
	{
		$regs = array();
		if (!self::isString($url))
			return false;

		if (!($parts = @parse_url($url)))
			return false;

		if (!isset($parts['scheme']) || ($parts['scheme'] != "http" && $parts['scheme'] != "https" && $parts['scheme'] != "ftp" && $parts['scheme'] != "gopher"))
			return false;

		if (!isset($parts['host']) || !self::isDomain($parts['host']))
			return false;

		if (isset($parts['user']) && !preg_match('/^([0-9a-z-]|[\_])*$/i', $parts['user']))
			return false;

		if (isset($parts['pass']) && !preg_match('/^([0-9a-z-]|[\_])*$/i', $parts['pass']))
			return false;

		if (isset($parts['path']) && !preg_match('/^[0-9a-z\/_\.@~\-=% ]*$/i', $parts['path']))
			return false;

		if (isset($parts['query']) && !preg_match('/^[0-9a-z?&=#\,\.\-\*%_+\[\]\\\\\/ ]*$/i', $parts['query']))
			return false;

		return true;
	}

	/**
	 * Check if a variable is a valid Domain
	 *
	 * @author RoyVanDeVorst
	 * @param mixed $url
	 * @return bool
	 */
	public static function isDomain($url)
	{
		// Check text domains
		if (preg_match('/^([a-z0-9_\-]{1,63}\.)*[a-z0-9]{2,63}$/', $url) === 1)
			return true;

		// Check IP
		if (self::isIP($url) === true)
			return true;

		// Fail
		return false;
	}

	/**
	 * Check if a variable is a valid IP address
	 *
	 * @since 1.5.0
	 * @param string $ip
	 * @return boolean
	 */
	public static function isIP($ip)
	{
		// Check IPv4
		if (preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/', $ip) === 1)
			return true;

		// Check IPv6
		if (preg_match('/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/', $ip) === 1)
			return true;

		// Fail
		return false;
	}

	/**
	 * Check if a variable is a valid email address
	 *
	 * @param mixed $email
	 * @return bool
	 */
	public static function isEmail($email)
	{
		// Check for multiple e-mail addresses (comma separated)
		if (strpos($email, ',') !== false)
		{
			// Remove spaces after commas
			$email = str_replace(', ', ',', $email);

			// Explode into an array
			$email = explode(',', $email);

			// Check all the addresses and return false if one of them doesn't validate
			foreach ($email as $address)
				if (self::isEmail($address) === false)
					return false;

			return true;
		}

		if (self::isString($email) && preg_match('/^[-_a-z0-9~.]+@\w[-_a-z0-9~.]+\.[a-z]{2}[a-z]*$/is', $email))
			return true;

		return false;
	}

	/**
	 * Check if a variable is valid HTML content
	 * (varifies the length of a text, excluding whitespaces and block-level HTML elements)
	 *
	 * @param mixed $content
	 * @return bool
	 */
	public static function isContent($content)
	{
		// When there's an image tag present, there also is content.
		if (strpos($content, '<img') !== false)
			return true;

		// When there's an object tag present, there also is content.
		if (strpos($content, '<object') !== false)
			return true;

		// When there's an object tag present, there also is content.
		if (strpos($content, '<iframe') !== false)
			return true;

		// When there's an embed tag present, there also is content.
		if (strpos($content, '<embed') !== false)
			return true;

		// When there's a script tag present, there also is content.
		if (strpos($content, '<script') !== false)
			return true;

		// When there's a input tag present, there also is content.
		if (strpos($content, '<input') !== false)
			return true;

		// A bool is not content
		if(Validator::isBool($content))
			return false;

		// Remove all the HTML tags.
		$content = strip_tags($content);

		// Remove all the spaces.
		$replaceStrings = array(' ', '&nbsp;', "\n", "\r", "\t");
		$content = str_replace($replaceStrings, '', $content);

		// Return if there's anything left.
		return ((bool) strlen($content));
	}

	/**
	 * Check if a variable is a valid MySQL ordering direction
	 * ("ASC" or "DESC")
	 *
	 * @param mixed $orderDir
	 * @return bool
	 */
	public static function isOrderDir($orderDir)
	{
		if (self::isString($orderDir))
		{
			$orderDir = strtoupper($orderDir);
			if ($orderDir === 'ASC' || $orderDir === 'DESC')
				return true;
		}
		return false;
	}

	/**
	 * Check if a variable is a valid hexidecimal color
	 *
	 * @param string $hex
	 * @param bool $allowEmpty Are empty/transparent colors allowed?
	 * @return bool
	 */
	public static function isColor($hex, $allowEmpty = true)
	{
		// Hexidecimal strings of either 3 or 6 characters with or without #-prefix
		$pattern = '/#?([0-9a-fA-F]{3}){1,2}$/';

		// get regex result
		$matches = array();
		preg_match($pattern, $hex, $matches);

		// see if given hex is valid
		if( count( $matches ) > 0 || ($allowEmpty && ($hex == "" || $hex == "#")))
			return( true );
		else
			return( false );
	}

	/**
	 * Check if string holds a valid date when matched against a specific format.
	 * Allows space, slash and hyphen or no seperator at all.
	 *
	 * @since 1.1.0
	 * @author TijsVanDerMeer
	 *
	 * @param string $date
	 * @param int $format
	 * @return bool
	 */
	public static function isDate($date, $format = self::DATE_DDMMYYYY)
	{
		if (!self::isString($date))
			return false;

		switch ($format)
		{
			case self::DATE_YYYYMMDD:
				$pattern = '^[0-9]{4}[\/\- ]?[0-1]{1}[0-9]{1}[\/\- ]?[0-3]{1}[0-9]{1}$';
				break;
			case self::DATE_MMDDYYYY:
				$pattern = '^[0-1]{1}[0-9]{1}[\/\- ]?[0-3]{1}[0-9]{1}[\/\- ]?[0-9]{4}$';
				break;
			case self::DATE_DDMMYYYY:
			default:
				$pattern = '^[0-3]{1}[0-9]{1}[\/\- ]?[0-1]{1}[0-9]{1}[\/\- ]?[0-9]{4}$';
		}

		return (bool) preg_match('/'.$pattern.'/i', $date);
	}

	/**
	 * Check if zipcode is in dutch standard
	 *
	 * @param string $string
	 * @param string $parts
	 * @return bool
	 */
	public static function isDutchZipCode($string, $parts = self::ZIPCODE_ALL)
	{
		if (!self::isString($string))
			return false;

		switch ($parts)
		{
			case self::ZIPCODE_NUMBERS:
				$pattern = '^[0-9]{4}$';
				break;
			case self::ZIPCODE_CHARS:
				$pattern = '^[a-z]{2}$';
				break;
			case self::ZIPCODE_ALL:
			default:
				$pattern = '^[0-9]{4} ?[a-z]{2}$';
		}

		return (bool) preg_match('/'.$pattern.'/i', $string);
	}

	/**
	 * Check if a Dutch bankaccount number is valid
	 * Works with both bankaccount and postbank
	 *
	 * @param string $account
	 * @return bool
	 */
	public static function isDutchBankAccount($account)
	{
		if (strlen($account) == 9)
		{ // Check bankrekening
			if (!is_numeric($account))
				return false;

			$sum = 0;
			for ($i = 0; $i < strlen($account); $i++)
				$sum += ($account{$i}*(9-$i));
			return (bool) (($sum % 11) == 0);
		}
		else
		{ // Check postbank
			$pattern = '^(p|p )?[0-9]{7}$';
			return (bool) preg_match('/'.$pattern.'/i', $account);
		}
	}

	/**
	 * Check if a Dutch phone number is valid
	 *
	 * @param string $number
	 * @return bool
	 */
	public static function isDutchPhonenumber($number)
	{
		$pattern = '^(\+31|0)(( |-)?[0-9]){9}$';
		return (bool) preg_match('/'.$pattern.'/i', $number);
	}

	/**
	 * Check if an IBAN number is valid
	 *
	 * @since 1.6.0
	 * @param string $iban
	 * @return boolean
	 */
	public static function isIBAN($iban)
	{
		// Calculate the country on the first two characters
		$country = substr($iban, 0, 2);

		switch($country)
		{
			// Add specific checks here

			// Default checks for different countries
			default:
				$regexes = array(
					'AD' => '/^AD[0-9]{2}[0-9]{8}[A-Z0-9]{12}$/',
					'AT' => '/^AT[0-9]{2}[0-9]{5}[0-9]{11}$/',
					'BA' => '/^BA[0-9]{2}[0-9]{6}[0-9]{10}$/',
					'BE' => '/^BE[0-9]{2}[0-9]{3}[0-9]{9}$/',
					'BG' => '/^BG[0-9]{2}[A-Z]{4}[0-9]{4}[0-9]{2}[A-Z0-9]{8}$/',
					'CH' => '/^CH[0-9]{2}[0-9]{5}[A-Z0-9]{12}$/',
					'CS' => '/^CS[0-9]{2}[0-9]{3}[0-9]{15}$/',
					'CY' => '/^CY[0-9]{2}[0-9]{8}[A-Z0-9]{16}$/',
					'CZ' => '/^CZ[0-9]{2}[0-9]{4}[0-9]{16}$/',
					'DE' => '/^DE[0-9]{2}[0-9]{8}[0-9]{10}$/',
					'DK' => '/^DK[0-9]{2}[0-9]{4}[0-9]{10}$/',
					'EE' => '/^EE[0-9]{2}[0-9]{4}[0-9]{12}$/',
					'ES' => '/^ES[0-9]{2}[0-9]{8}[0-9]{12}$/',
					'FR' => '/^FR[0-9]{2}[0-9]{10}[A-Z0-9]{13}$/',
					'FI' => '/^FI[0-9]{2}[0-9]{6}[0-9]{8}$/',
					'GB' => '/^GB[0-9]{2}[A-Z]{4}[0-9]{14}$/',
					'GI' => '/^GI[0-9]{2}[A-Z]{4}[A-Z0-9]{15}$/',
					'GR' => '/^GR[0-9]{2}[0-9]{7}[A-Z0-9]{16}$/',
					'HR' => '/^HR[0-9]{2}[0-9]{7}[0-9]{10}$/',
					'HU' => '/^HU[0-9]{2}[0-9]{7}[0-9]{1}[0-9]{15}[0-9]{1}$/',
					'IE' => '/^IE[0-9]{2}[A-Z0-9]{4}[0-9]{6}[0-9]{8}$/',
					'IS' => '/^IS[0-9]{2}[0-9]{4}[0-9]{18}$/',
					'IT' => '/^IT[0-9]{2}[A-Z]{1}[0-9]{10}[A-Z0-9]{12}$/',
					'LI' => '/^LI[0-9]{2}[0-9]{5}[A-Z0-9]{12}$/',
					'LU' => '/^LU[0-9]{2}[0-9]{3}[A-Z0-9]{13}$/',
					'LT' => '/^LT[0-9]{2}[0-9]{5}[0-9]{11}$/',
					'LV' => '/^LV[0-9]{2}[A-Z]{4}[A-Z0-9]{13}$/',
					'MK' => '/^MK[0-9]{2}[A-Z]{3}[A-Z0-9]{10}[0-9]{2}$/',
					'MT' => '/^MT[0-9]{2}[A-Z]{4}[0-9]{5}[A-Z0-9]{18}$/',
					'NL' => '/^NL[0-9]{2}[A-Z]{4}[0-9]{10}$/',
					'NO' => '/^NO[0-9]{2}[0-9]{4}[0-9]{7}$/',
					'PL' => '/^PL[0-9]{2}[0-9]{8}[0-9]{16}$/',
					'PT' => '/^PT[0-9]{2}[0-9]{8}[0-9]{13}$/',
					'RO' => '/^RO[0-9]{2}[A-Z]{4}[A-Z0-9]{16}$/',
					'SE' => '/^SE[0-9]{2}[0-9]{3}[0-9]{17}$/',
					'SI' => '/^SI[0-9]{2}[0-9]{5}[0-9]{8}[0-9]{2}$/',
					'SK' => '/^SK[0-9]{2}[0-9]{4}[0-9]{16}$/',
					'TN' => '/^TN[0-9]{2}[0-9]{5}[0-9]{15}$/',
					'TR' => '/^TR[0-9]{2}[0-9]{5}[A-Z0-9]{17}$/'
				);

				if (isset($regexes[$country]) && preg_match($regexes[$country], $iban))
					return true;

				break;
		}

		return false;
	}

	/*** Array variables ***/
	/**
	 * Check if a variable is a valid array
	 *
	 * @param mixed $array
	 * @param bool $allowEmpty Validates true when the array is empty
	 * @return bool
	 */
	public static function isArray($array, $allowEmpty = true)
	{
		if (isset($array) && is_array($array) && ($allowEmpty || count($array) > 0))
			return true;
		else
			return false;
	}

	/**
	 * Check if a variable is a valid enumeration
	 * Validates true if $enum is within the list $allowedValues
	 *
	 * @param mixed $enum
	 * @param mixed $allowedValues
	 * @return bool
	 */
	public static function isEnum($enum, $allowedValues)
	{
		if (strlen($enum) && self::isArray($allowedValues) && in_array($enum, $allowedValues))
			return true;
		else
			return false;
	}

	/*** Objects and instances ***/
	/**
	 * Check if a classname is valid
	 *
	 * @param string $class
	 * @return bool
	 */
	public static function isClass($class)
	{
		if (self::isString($class) && class_exists($class))
			return true;
		else
			return false;
	}

	/**
	 * Check if an object is valid
	 *
	 * @param mixed $object
	 * @param mixed $type Validates if object is an instance of this class
	 * @return bool
	 */
	public static function isObject($object, $type = false)
	{
		if (isset($object) && is_object($object))
		{
			if(self::isString($type))
			{
				if(strtolower(get_class($object)) == strtolower($type))
					return true;
				else
					return false;
			}
			else
				return true;
		}
		else
			return false;
	}

	/*** HTML Headers ***/
	/**
	 * Check if a variable is a valid URL within the current domain
	 *
	 * @param mixed $url
	 * @return bool
	 */
	public static function isValidReferer($url)
	{
		if(self::isURL($url) && strpos(Cms::$httpPath, $url) == 0)
			return true;
		else
			return false;
	}

	/*** Custom ***/
	/**
	 * Checks if the string is valid by using the regex $pattern
	 *
	 * @param string $string
	 * @param string $pattern Regex pattern
	 * @return bool
	 */
	public static function customCheck($string, $pattern)
	{
		return (bool) preg_match($pattern, $string);
	}

	/**
	 * Check if a straing is a valid username: only aplhanumeric characters plus _-@. minimum of three chars.
	 *
	 * @since 1.1.0
	 * @param mixed $username
	 * @return bool
	 */
	public static function isUsername($username)
	{
		if (self::isString($username) && preg_match('/^[\w@\-\.]{3,}$/is', $username))
			return true;

		return false;
	}

	/**
	 * Captcha specific validator
	 *
	 * @since 1.0.2
	 * @author DanielPolman
	 *
	 * @param string $string
	 * @param int $id
	 * @return Captcha
	 */
	public static function isCaptchaCorrect($string, $id)
	{
		$captcha = new Captcha($id);

		return $captcha->validate($string);
	}

	/**
	 * Check if a string is empty
	 *
	 * @since 1.2.0
	 * @param string $string
	 * @return bool
	 */
	public static function isBlank($string)
	{
		if (trim($string) == '')
			return true;
		else
			return false;
	}

	/**
	 * Check if a string is a valid javascript variable name.
	 *
	 * @since 1.2.0
	 * @author Rob Tiemens
	 * @param string $string
	 * @return bool
	 */
	public static function isJavascriptVar($string)
	{
		return (self::isString($string) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/s', $string));
	}

	/**
	 * Check if a string is a valid javascript function name.
	 *
	 * @since 1.7.0
	 * @param string $string
	 * @return boolean
	 */
	public static function isJavascriptFunction($string)
	{
		// Get function name
		if (strpos('(', $string) !== false)
			$string = substr($string, 0, strpos('(', $string));

		// Explode on period
		$parts = explode('.', $string);

		// Check all parts
		foreach ($parts as $part)
		{
			if (!self::isJavascriptVar($part))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if time is in the following format, hh:mm/hh:mm:ss
	 *
	 * @since 1.4.0
	 * @param string $naturalTime
	 * @param string $format
	 * @return bool
	 */
	public static function isNaturalTime($naturalTime, $format = self::TIME_HHMM)
	{
		if (!self::isString($naturalTime))
			return false;

		switch ($format)
		{
			case self::TIME_HHMMSS:
				$pattern = '^([0-9]){1,5}:([0-5][0-9]):([0-5][0-9])$';
				break;
			case self::TIME_HHMM:
			default:
				$pattern = '^([0-9]){1,5}:([0-5][0-9])$';
		}

		return (bool) preg_match('/'.$pattern.'/i', $naturalTime);
	}

	/**
	 * Check if a given string is valid JSON
	 *
	 * @since 1.7.0
	 * @param string $string
	 * @return boolean
	 */
	public static function isJson($string)
	{
		if(!self::isString($string))
			return false;

		$tmp = json_decode($string);

		if(function_exists('json_last_error'))
		{
			if(json_last_error() == JSON_ERROR_NONE)
				return true;
			else
				return false;
		}

		return $tmp !== null;
	}


	/**
	 * Check if a Dutch license plate is valid
	 * @see http://www.rdw.nl/Particulier/Paginas/Uitleg-over-de-cijfers-en-letters-op-de-kentekenplaat.aspx
	 *
	 * @since 1.8.0
	 * @param string $licensePlate
	 * @return boolean
	 */
	public function isDutchLicensePlate($licensePlate)
	{
		$patterns = array(
				'^([A-Z]{2})-([0-9]{2})-([0-9]{2})$', // AA-00-00
				'^([0-9]{2})-([0-9]{2})-([A-Z]{2})$', // 00-00-AA
				'^([0-9]{2})-([A-Z]{2})-([0-9]{2})$', // 00-AA-00
				'^([A-Z]{2})-([0-9]{2})-([A-Z]{2})$', // AA-00-AA
				'^([A-Z]{2})-([A-Z]{2})-([0-9]{2})$', // AA-AA-00
				'^([0-9]{2})-([A-Z]{2})-([A-Z]{2})$', // 00-AA-AA
				'^([0-9]{2})-([A-Z]{3})-([0-9]{1})$', // 00-AAA-0
				'^([0-9]{1})-([A-Z]{3})-([0-9]{2})$', // 0-AAA-00
				'^([A-Z]{2})-([0-9]{3})-([A-Z]{1})$', // XX-000-X
				'^([A-Z]{1})-([0-9]{3})-([A-Z]{2})$', // X-000-XX
		);

		foreach($patterns as $pattern)
		{
			if((bool) preg_match('/'.$pattern.'/i', $licensePlate))
				return true;
		}

		return false;
	}

	/**
	 * Check if a Dutch BSN number is valid according to the 11-check
	 *
	 * @since 1.8.0
	 * @param string $bsn
	 * @return bool
	 *
	 * @see http://nl.wikipedia.org/wiki/Elfproef
	 */
	public static function isBSN($bsn)
	{
		if(strlen($bsn) == 8)
			$bsn = "0" . $bsn;

		if (strlen($bsn) == 9)
		{
			if (!is_numeric($bsn))
				return false;

			$sum = 0;
			for ($i = 0; $i < strlen($bsn); $i++)
			{
				if($i == 8)
					$sum -= $bsn{$i};
				else
					$sum += ($bsn{$i}*(9-$i));
			}

			return (bool) (($sum % 11) == 0);
		}
		else
			return false;
	}
}

?>
