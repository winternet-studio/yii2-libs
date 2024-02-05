<?php
/**
 * Force using message formatter fallback method
 *
 * Skip using PHP intl extension even if it is available, in case it is of an ICU version that is too old and server cannot be upgraded.
 *
 * Based on ideas from https://stackoverflow.com/questions/48672978/yii2-how-to-force-using-fallback-messageformatter-method
 *
 * Usage example:
 * ```
 * $fbFormatter = new \app\components\MessageFormatterFallback();
 * echo $fbFormatter->format(Yii::t('app', '{number,plural,=0{# available} =1{# available} other{# available}}'), ['number' => 45], Yii::$app->language);
 * ```
 */

namespace winternet\yii2;

class MessageFormatterFallback extends \yii\i18n\MessageFormatter {
    private $_errorCode = 0;
    private $_errorMessage = '';

	public function format($pattern, $params, $language) {
		$this->_errorCode = 0;
		$this->_errorMessage = '';

		if ($params === []) {
			return $pattern;
		}

		return $this->fallbackFormat($pattern, $params, $language);
	}
}
