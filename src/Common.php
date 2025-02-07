<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class Common extends Component {

	public static $bufferTableConfig = [
		'tableName' => null,
		'keyColumn' => null,
		'valueColumn' => null,
		'expireColumn' => null,
	];

	public static $runtime = [];

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
			if (@Yii::$app->params['currMultiLingualLanguage']) {
				$language = Yii::$app->params['currMultiLingualLanguage'];
			} else {
				$language = substr(Yii::$app->language, 0, 2);
			}
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

	/**
	 * Builds a string with multiple translations of a piece of text
	 *
	 * @param array $array : Array output from [[parseMultiLang()]] with $lang='ALL' as argument
	 * @return string
	 */
	public static function buildMultiLang($array) {
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
	 * Set temporary language to use for multilingual strings parsed by buildMultiLang()
	 *
	 * @param string $newIetfLanguage : 4-letter language code, eg. `en-US`
	 * @param string $newLanguage : 2-letter language code used in the string of multiple translations if it's not the first 2 characters of the IETF code, eg. `en`.
	 *                              In this case you also have to use `Yii::$app->params['currMultiLingualLanguage']` to hold the current 2-letter language code.
	 */
	public static function temporaryLanguage($newIetfLanguage, $newLanguage = null) {
		static::$runtime['originalYiiLanguage'] = Yii::$app->language;
		Yii::$app->language = $newIetfLanguage;

		if (isset(Yii::$app->params['currMultiLingualLanguage'])) {
			static::$runtime['originalMultiLingualLanguage'] = Yii::$app->params['currMultiLingualLanguage'];
			Yii::$app->params['currMultiLingualLanguage'] = $newLanguage;
		}
	}

	public static function restoreLanguage() {
		if (@static::$runtime['originalYiiLanguage']) {
			Yii::$app->language = static::$runtime['originalYiiLanguage'];
			static::$runtime['originalYiiLanguage'] = null;
		}

		if (isset(static::$runtime['originalMultiLingualLanguage'])) {
			Yii::$app->params['currMultiLingualLanguage'] = static::$runtime['originalMultiLingualLanguage'];
		}
	}

	/**
	 * Determine language from browser header
	 *
	 * Example header: `Accept-Language: de-DE,de;q=0.9,en;q=0.8`
	 *
	 * @param array $supportedLanguages : Supported languages, eg. `['en-US', 'da-DK', 'de-DE']`
	 * @return string|null : Eg. `da-DK` if the language is found
	 */
	public static function getLanguageFromBrowser($supportedLanguages) {
		foreach (\Yii::$app->request->getAcceptableLanguages() as $acceptableLanguage) {  //copied from yii\web\Request class. Example: `['en-GB', 'en']`. Yii puts them in their prioritized order.
			$acceptableLanguage = str_replace('_', '-', strtolower($acceptableLanguage));
			foreach ($supportedLanguages as $language) {
				$normalizedLanguage = str_replace('_', '-', strtolower($language));

				if (
					$normalizedLanguage === $acceptableLanguage // en-us==en-us
					|| strpos($acceptableLanguage, $normalizedLanguage . '-') === 0 // en==en-us
					|| strpos($normalizedLanguage, $acceptableLanguage . '-') === 0 // en-us==en
				) {
					return $language;
				}
			}
		}
		return null;
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
	 * Set a value in the temporary buffer table
	 *
	 * Good for information that doesn't fit into the existing table structure and is temporary anyway.
	 *
	 * Originally a copy from winternet-studio/jensenfw2.
	 *
	 * @param string|integer $key : Number or string with the key
	 * @param string|integer $value : Number or string with the value to store
	 * @param string $expiration : The expiration date (UTC) of this value in MySQL format (yyyy-mm-dd or yyyy-mm-dd hh:mm:ss)
	 *   - or number of hours to expire (eg. 6 hours: `6h`)
	 *   - or days to expire (eg. 14 days: `14d`)
	 *   - or `NOW` in order to delete a buffer value before current expiration (when overwriting an existing one)
	 * @return void
	 */
	public static function setBufferValue($key, $value, $expiration = false) {
		$config = static::getBufferConfig();

		// Auto-overwrite any record with the same key
		$sqlRelative = null;
		$sql = "REPLACE INTO `". $config->tableName ."` SET `". $config->keyColumn ."` = :key, `". $config->valueColumn ."` = :value";
		$parameters = [
			'key' => $key,
			'value' => $value,
		];
		if ($expiration) {
			if (preg_match('|^\\d{2,4}-\\d{1,2}-\\d{1,2}$|', $expiration) || preg_match('|^\\d{2,4}-\\d{1,2}-\\d{1,2}\\s+\\d{1,2}:\\d{2}:\\d{2}$|', $expiration)) {
				//do nothing, use raw value
			} elseif (preg_match("/^\\d+[dh]$/i", $expiration)) {
				$sqlRelative = 'NOW() + INTERVAL '. str_replace(['d', 'h'], [' DAY', ' HOUR'], $expiration);
			} elseif ($expiration == 'NOW') {
				$expiration = '2000-01-01 00:00:00';
			} else {
				throw new \Exception('Invalid expiration date for setting a value in temporary buffer table.');
			}
			if ($sqlRelative) {
				$sql .= ", `". $config->expireColumn ."` = ". $sqlRelative;
			} else {
				$sql .= ", `". $config->expireColumn ."` = :expiration";
				$parameters['expiration'] = (new \DateTime($expiration))->format('Y-m-d H:i:s');
			}
		}

		$affectedRows = \Yii::$app->db->createCommand($sql, $parameters)->execute();
	}

	/**
	 * Get a value from the temporary buffer table
	 *
	 * Also cleans the buffer table once per session.
	 *
	 * @param string $key : Key to get the value for
	 * @return string|array : String with the value, or empty array if key was not found
	 */
	public static function getBufferValue($key) {
		$config = static::getBufferConfig();

		// Clean up the buffer once per session
		if (Yii::$app->has('session')) {  //skip cleaning when no session available (most often means running in CLI)
			$session = Yii::$app->session;
			if (Yii::$app->request->isConsoleRequest || !$session || !$session->get('_jfw_cleaned_buffer')) {

				\Yii::$app->db->createCommand("DELETE FROM `". $config->tableName ."` WHERE `". $config->expireColumn ."` IS NOT NULL AND `". $config->expireColumn ."` < UTC_TIMESTAMP()")->execute();

				if ($session) {
					$session->set('_jfw_cleaned_buffer', true);
				}
			}
		}

		// Get the value
		$sql = "SELECT `". $config->valueColumn ."` FROM `". $config->tableName ."` WHERE `". $config->keyColumn ."` = :key AND (`". $config->expireColumn ."` IS NULL OR `". $config->expireColumn ."` > UTC_TIMESTAMP())";
		return \Yii::$app->db->createCommand($sql, [
			'key' => $key,
		])->queryScalar();
	}

	/**
	 * Get an item from the buffer table by searching for a value
	 */
	public static function searchBufferValue($value) {
		$config = static::getBufferConfig();
		return (new \yii\db\Query())->from($config->tableName)->where([$config->valueColumn => $value])->one();
	}

	public static function getBufferConfig() {
		return (object) [
			'tableName' => static::$bufferTableConfig['tableName'] ?? 'buffer',
			'keyColumn' => static::$bufferTableConfig['keyColumn'] ?? 'tmpd_key',
			'valueColumn' => static::$bufferTableConfig['valueColumn'] ?? 'tmpd_value',
			'expireColumn' => static::$bufferTableConfig['expireColumn'] ?? 'tmpd_date_expire',
		];
	}

	/**
	 * @param array $options : Possible keys:
	 *   - `absoluteUrl` : set true to return full URL instead of only the part after the domain name
	 *   - `skipConsolePrefix` : set true to not include "Console command: " prefix for console scripts
	 */
	public static function getScriptReference($options = []) {
		if (Yii::$app->request->isConsoleRequest) {
			if (@$options['skipConsolePrefix']) {
				return implode(' ', Yii::$app->request->getParams());
			} else {
				return 'Console command: '. implode(' ', Yii::$app->request->getParams());
			}
		} else {
			if (@$options['absoluteUrl']) {
				return Yii::$app->request->getAbsoluteUrl();
			} else {
				return Yii::$app->request->getUrl();
			}
		}
	}
}
