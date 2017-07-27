<?php
namespace winternet\yii2;

use yii\base\Component;

class ModelHelper extends Component {
	public static function requiredAttributes($model) {
		/*
		DESCRIPTION:
		- return a list of model attributes that are required
		INPUT:
		- $model
		OUTPUT:
		- array of attributes
		*/

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
			new \app\components\Error('Invalid list of attributes to filtering.', ['Attributes' => $all_attributes]);
		}
		if (!is_array($attributes_to_keep)) {
			new \app\components\Error('Invalid list of attributes to keep when filtering attributes.', ['Attributes' => $attributes_to_keep]);
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
				} elseif ($value['class']) {
					// always leave special entries intact (eg. class=yii\grid\ActionColumn)
					$output[] = $value;
					continue;
				} else {
					new \app\components\Error('Attributes name not found when filtering attributes.', ['Attributes' => $all_attributes]);
				}
			} else {
				new \app\components\Error('Invalid value for filtering attributes.', ['Attributes' => $all_attributes]);
			}

			if (in_array($name, $attributes_to_keep)) {
				$output[] = $value;
			}
		}

		return $output;
	}
}
