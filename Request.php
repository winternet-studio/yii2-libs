<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class Request extends Component {

	/**
	 * Handle action parameters in controller, incl. numeric value conversion
	 *
	 * The purpose is to solve the issue when the parameter has a dash or other special character in it prohibiting the normal parameters in the action method.
	 */
	public static function queryParam($name, $default = '-DEFAULT-') {
		$value = \Yii::$app->request->get($name);
		if (empty($value) && !is_numeric($value)) {
			if ($default === '-DEFAULT-') {
				throw new \yii\web\BadRequestHttpException('Missing required parameter: '. $name);
			} else {
				return $default;
			}
		}

		// Handle type conversion for numeric values as well
		if (is_numeric($value)) {
			if (is_int($default) || is_float($default)) {
				if (floor($value) != (float) $value) {
					$value = (float) $value;
				} else {
					$value = (integer) $value;
				}
			}
		}

		return $value;
	}

}
