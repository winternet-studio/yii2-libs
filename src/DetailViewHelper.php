<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class DetailViewHelper extends Component {
	public static function fromAllowedValues($model, $attribute) {
		return
			[
				'attribute' => $attribute,
				'value' => $model::allowedValues($attribute)[ $model->$attribute ],
			];
	}
}
