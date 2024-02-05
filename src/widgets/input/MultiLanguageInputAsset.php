<?php
namespace winternet\yii2\widgets\input;

use yii\web\AssetBundle;

class MultiLanguageInputAsset extends AssetBundle {
	/**
	 * @var string
	 */
	public $sourcePath = __DIR__ .'/../../assets/MultiLanguageInput';

	// public $publishOptions = ['forceCopy' => true];

	/**
	 * @var array
	 */
	public $js = [
		'MultiLanguageInput.js',
	];

	/**
	 * @var array
	 */
	public $depends = [
		'yii\web\JqueryAsset',
		// 'yii\bootstrap\BootstrapAsset',
	];

}
