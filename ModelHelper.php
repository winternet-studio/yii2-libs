<?php
namespace winternet\yii2;

use yii\base\Component;

class ModelHelper extends Component {
	/**
	 * Disallow mass assignment while retaining validation of the attribute
	 *
	 * @param array $all : List of attributes to go through
	 * @param array $disallow : Attributes to disallow mass assignment for. Other attributes in the $all array are not touched.
	 * @return array : The modified list of attributes
	 */
	public static function disallowMassAssign($all, $disallow) {
		return array_map(function($attribute) use (&$disallow) {
			if (in_array($attribute, $disallow) && $attribute[0] !== '!') {
				$attribute = '!'. $attribute;  //prefix with ! (= no massive assign but still validated)
			}
			return $attribute;
		}, $all);
	}

	/**
	 * Alias for disallowMassAssign()
	 */
	public static function noMassAssign($all, $disallow) {
		return self::disallowMassAssign($all, $disallow);
	}

	/**
	 * Disallow mass assignment while retaining validation of the attribute
	 *
	 * @param array $all : List of attributes to go through
	 * @param array $allow : Attributes to allow mass assignment for. All others in $all will be set to "not allowed".
	 * @return array : The modified list of attributes
	 */
	public static function allowMassAssign($all, $allow) {
		// Prefix with ! (= no massive assign but still validated)
		return array_map(function($attribute) use (&$allow) {
			if (!in_array($attribute, $allow)) {
				$attribute = '!'. $attribute;  //prefix with ! (= no massive assign but still validated)
			} elseif ($attribute[0] === '!') {  //remove existing 
				$attribute = substr($attribute, 1);
			}
			return $attribute;
		}, $all);
	}

	/**
	 * Return a list of model attributes that are required
	 *
	 * @param yii\base\Model $model
	 * @return array : Attributes
	 */
	public static function requiredAttributes($model) {
		$required = [];

		foreach ($model->rules() as $rule) {
			if (is_string($rule[1]) && $rule[1] == 'required') {
				if (is_string($rule[0])) {
					$required[] = $rule[0];
				} elseif (is_array($rule[0])) {
					$required = array_merge($required, $rule[0]);
				}
			}
		}

		return $required;
	}

	public static function requiredAttributesCss($model, $options = []) {
		$defaults = [
			'selector' => 'label:after',
			'properties' => 'content: " •"; color: #e10000; position: relative; top: -3px',
		];
		$options = array_merge($defaults, $options);

		$required_attributes = self::requiredAttributes($model);
		$class_name = get_class($model);
		$class_name = strtolower(substr($class_name, strrpos($class_name, "\\")+1));

		$css = '';
		foreach ($required_attributes as $attr) {
			$css .= '.field-'. $class_name .'-'. strtolower($attr) .' '. $options['selector'] .', ';
		};
		$css = substr($css, 0, strlen($css)-2) .' {'. $options['properties'] .'}';
		return $css;
	}

	public static function filterAttributes($all_attributes, $attributes_to_keep) {
		/*
		DESCRIPTION:
		- takes an array of attributes in the form they are provided to eg. GridView or DetailView, and return new array where only the desired ones have been kept
		INPUT:
		- $all_attributes : entire list of attributes (and possibly other entries) to be filtered
		- $attributes_to_keep : list of attributes to keep (other entries are kept as well)
		OUTPUT:
		- array of attributes
		*/
		if (!is_array($all_attributes)) {
			new \winternet\yii2\UserException('Invalid list of attributes to filtering.', ['Attributes' => $all_attributes]);
		}
		if (!is_array($attributes_to_keep)) {
			new \winternet\yii2\UserException('Invalid list of attributes to keep when filtering attributes.', ['Attributes' => $attributes_to_keep]);
		}

		$output = [];
		foreach ($all_attributes as $value) {
			if (is_string($value)) {
				$colon = strpos($value, ':');
				if ($colon === false) {
					$name = $value;
				} else {
					$name = substr($value, 0, $colon);
				}
			} elseif (is_array($value)) {
				if ($value['attribute']) {
					$name = $value['attribute'];

					if (preg_match("/.\\../", $name)) {
						// this attribute is an attribute in a related table => try to get the original attribute from the dropdown input field in the 'filter' param
						if ($value['filter']) {
							if (preg_match("/name=\".*\\[(.*)]\"/U", $value['filter'], $match)) {
								$name = $match[1];
							}
						}
					}
				} elseif ($value['class']) {
					// always leave special entries intact (eg. class=yii\grid\ActionColumn)
					$output[] = $value;
					continue;
				} else {
					new \winternet\yii2\UserException('Attributes name not found when filtering attributes.', ['Attributes' => $all_attributes]);
				}
			} else {
				new \winternet\yii2\UserException('Invalid value for filtering attributes.', ['Attributes' => $all_attributes]);
			}

			if (in_array($name, $attributes_to_keep)) {
				$output[] = $value;
			}
		}

		return $output;
	}

	/**
	 * Dynamically create an ActiveRecord model
	 *
	 * @param array $params : Array with these entries:
	 *   - `tableName` (req.) : Name of database table
	 *
	 * @return string : Class name to be instantiated
	 */
	public static function createActiveRecordModel($params) {
		if (preg_match("/[^a-z0-9_]/i", $params['tableName'])) {
			\Yii::$app->system->error('Table name for creating ActiveRecord class has invalid characters.', ['TableName' => $params['tableName']]);
		}

		$className = $params['tableName'];

		$phpCode  = "class ". $className ." extends \yii\db\ActiveRecord { public static function tableName() {return '". $params['tableName'] ."';} }". PHP_EOL;
		eval($phpCode);

		return $className;
	}
}
