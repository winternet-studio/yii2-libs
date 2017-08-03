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

	public static function parseMultiLang($str, $lang = null) {
		/*
		DESCRIPTION:
		- parses a string with multiple translations of a piece of text
		INPUT:
		- $str : string in the format: EN=Text in English ,,, ES=Text in Spanish
			- unlimited number of translations
			- upper case of language identifier is optional
			- spaces are allowed around both identifiers and texts (will be trimmed)
		- $lang : use specific language instead of Yii's app language
			- set to 'ALL' to return all translations in an array (key being the language in lower case)
				- OBS! You still need to check if an array was returned because if no translations were found the original string is just returned
		OUTPUT:
		- string, or array if $lang='ALL' and at least one translation was found
		- if no matches found, the raw string is returned
		- if language is not found, the first language is returned
		*/
		if ($lang === null) {
			$lang = substr(Yii::$app->language, 0, 2);
		}

		$all = [];

		$str = (string) $str;
		if (!$str) {
			return $str;
		} else {
			if (preg_match('/,,,\\s*[a-zA-Z]{2}\\s*=/', $str)) {
				$str = explode(',,,', $str);
				foreach ($str as &$a) {
					if (preg_match('/^\\s*([a-zA-Z]{2})\\s*=\\s*(.*?)\\s*$/s', $a, $match)) {
						$clang = strtolower($match[1]);

						if ($lang == 'ALL') {
							$all[$clang] = $match[2];
						} else {
							if ($lang == $clang) {
								return $match[2];
							}
						}

					}
				}

				if ($lang == 'ALL') {
					return $all;
				} else {
					$b = explode('=', $str[0]);  //fallback to first language
					return trim($b[1]);
				}

			} elseif (preg_match('/^\\s*([a-zA-Z]{2})\\s*=\\s*(.*?)\\s*$/s', $str, $match)) {
				if ($lang == 'ALL') {
					$clang = strtolower($match[1]);
					$all[$clang] = $match[2];
					return $all;
				} else {
					return $match[2];
				}

			} else {
				return $str;
			}
		}
	}
}
