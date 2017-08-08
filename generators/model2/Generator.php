<?php
namespace winternet\yii2\generators\model2;

use Yii;

/**
 * @inheritdoc
 */
class Generator extends \yii\gii\generators\model\Generator {

	public $enableI18N = true;

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Model2 Generator';
	}

	/**
	 * @inheritdoc
	 */
	public function getDescription() {
		return 'This is a customized version of the default Yii Model generator. Additionally it also allows to save and restore generator settings!';
	}
}
