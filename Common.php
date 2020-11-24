<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class Common extends Component {
	public static function processAjaxSubmit($options = []) {
		// TODO: change all uses to use the FormHelper instead
		return FormHelper::processAjaxSubmit($options);
	}

	public static function processAjaxSubmitError($options = []) {
		// TODO: change all uses to use the FormHelper instead
		return FormHelper::processAjaxSubmitError($options);
	}

	public static function addResultErrors($result, &$model, $options = []) {
		// TODO: change all uses to use the FormHelper instead
		return FormHelper::addResultErrors($result, $model, $options);
	}

	/**
	 * Parses a string with multiple translations of a piece of text
	 *
	 * @param string $str : String in the format: `EN=Text in English ,,, ES=Text in Spanish`
	 *   - unlimited number of translations
	 *   - upper case of language identifier is optional
	 *   - spaces are allowed around both identifiers and texts (will be trimmed)
	 * @param string $language : Use specific language instead of Yii's app language (2-letter ISO 639-1 code)
	 *   - set to `ALL` to return all translations in an array (key being the language in lower case)
	 *     - OBS! You still need to check if an array was returned because if no translations were found the original string is just returned
	 * @return string|array : String, or array if $language=`ALL` and at least one translation was found.
	 *   - if no matches found, the raw string is returned
	 *   - if language is not found, the first language is returned
	 */
	public static function parseMultiLang($text, $language = null) {
		if ($language === null) {
			$language = substr(Yii::$app->language, 0, 2);
		}

		$all = [];

		$text = (string) $text;
		if (!$text) {
			return $text;
		} else {
			$regExp = '/^\\s*([a-zA-Z]{2})\\s*=\\s*(.*?)\\s*$/s';
			if (preg_match('/,,,\\s*[a-zA-Z]{2}\\s*=/', $text)) {
				// multiple languages
				$text = explode(',,,', $text);
				foreach ($text as &$a) {
					if (preg_match($regExp, $a, $match)) {
						$clang = strtolower($match[1]);

						if ($language == 'ALL') {
							$all[$clang] = $match[2];
						} else {
							if ($language == $clang) {
								return $match[2];
							}
						}

					}
				}

				if ($language == 'ALL') {
					return $all;
				} else {
					$b = explode('=', $text[0], 2);  //fallback to first language
					return trim($b[1]);
				}

			} elseif (preg_match($regExp, $text, $match)) {
				// only a single language
				if ($language == 'ALL') {
					$clang = strtolower($match[1]);
					$all[$clang] = $match[2];
					return $all;
				} else {
					return $match[2];
				}

			} else {
				// no specific language, just regular text
				return $text;
			}
		}
	}

	public static function buildMultiLang($array) {
		/*
		DESCRIPTION:
		- builds a string with multiple translations of a piece of text
		INPUT:
		- $array : array output from parseMultiLang() with $lang='ALL' as argument
		OUTPUT:
		- string
		*/
		if (is_string($array)) {
			return $array;
		} else {
			$output = [];
			foreach ($array as $lang => $string) {
				$output[] = strtoupper($lang) .'='. $string;
			}
			return implode(',,,', $output);
		}
	}

	/**
	 * Change the timezone of a datetime/timestamp
	 *
	 * @param mixed $dateTime : MySQL datetime or Unix timestamp (or anything the DateTime() constructor accepts)
	 * @param string $currTimeZone : (opt.) Current timezone of the timestamp. Assume UTC if null.
	 * @param string $newTimeZone : (opt.) New timezone of the timestamp. Default current user's timezone (in attribute usr_timezone). If no timezone found no conversion is done.
	 * @param string $format : Format the datetime according to this format instead of returning the DateTime object
	 *
	 * @return DateTime|string : DateTime object or formatted datetime as a string
	 */
	public static function changeTimezone($dateTime, $currTimeZone = null, $newTimeZone = null, $format = null) {
		if ($dateTime) {
			if (!$currTimeZone) {
				$currTimeZone = 'UTC';
			}
			$timestamp = new \DateTime((is_numeric($dateTime) ? '@'. $dateTime : $dateTime), new \DateTimeZone($currTimeZone));

			if (!$newTimeZone && !Yii::$app->user->isGuest) {
				if (Yii::$app->user->identity->usr_timezone) {
					 $newTimeZone = Yii::$app->user->identity->usr_timezone;
				}
			}
			if ($newTimeZone) {
				$timestamp->setTimezone(new \DateTimeZone($newTimeZone));
			}
			if ($format) {
				return $timestamp->format($format);
			} else {
				return $timestamp;
			}
		} else {
			return null;
		}
	}

	/**
	 * @param array $options : Possible keys:
	 *   - `absoluteUrl` : set true to return full URL instead of only the part after the domain name
	 *   - `skipConsolePrefix` : set true to not include "Console command: " prefix for console scripts
	 */
	public static function getScriptReference($options = []) {
		if (Yii::$app->request->isConsoleRequest) {
			if ($options['skipConsolePrefix']) {
				return implode(' ', Yii::$app->request->getParams());
			} else {
				return 'Console command: '. implode(' ', Yii::$app->request->getParams());
			}
		} else {
			if ($options['absoluteUrl']) {
				return Yii::$app->request->getAbsoluteUrl();
			} else {
				return Yii::$app->request->getUrl();
			}
		}
	}
}
