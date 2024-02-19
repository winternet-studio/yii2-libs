<?php
namespace winternet\yii2\validators;

use Yii;
use yii\validators\Validator;

class UnchangeableValidator extends Validator {

	/**
	 * @var boolean : Value passed to the `$identical` argument for `isAttributeChanged()`
	 */
	public $identical = false;

	public function validateAttribute($model, $attribute) {
		if ( ! $model->isNewRecord && $model->isAttributeChanged($attribute, $this->identical)) {
			$this->addError($model, $attribute, ($this->message ? $this->message : Yii::t('yii', '{attribute} cannot be changed.')));
		}
	}
}
