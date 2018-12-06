<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

class GridViewHelper extends Component {
	public static function filterFromAllowedValues($searchModel, $attribute, $options = []) {
		return
			[
				'attribute' => $attribute,
				'filter' => Html::activeDropDownList($searchModel, $attribute, $searchModel::allowedValues($attribute), ['prompt' => (array_key_exists('prompt', $options) ? $options['prompt'] : ''), 'class' => 'form-control'. ($options['class'] ? ' '. $options['class'] : ''), 'style' => 'width: '. ($options['width'] ? $options['width'] : '85px') ]),
				'value' => function($model) {
					return $model::allowedValues($attribute)[ $model->$attribute ],
				},
			];
	}

	/**
	 * @param $dataArray : Example: `\app\models\Customer::find()->orderBy('cust_name')->all()`
	 */
	public static function filterFromArray($searchModel, $attribute, $dataArray, $dataArrayKey, $dataArrayValue $options = []) {
		return
			[
				'attribute' => ($options['relatedAttribute'] ? $options['relatedAttribute'] : $attribute),
				'filter' => Html::activeDropDownList($searchModel, $attribute, ArrayHelper::map($dataArray, $dataArrayKey, $dataArrayValue), ['prompt' => (array_key_exists('prompt', $options) ? $options['prompt'] : ''), 'class' => 'form-control'. ($options['class'] ? ' '. $options['class'] : '') ]),
			];
	}
}
