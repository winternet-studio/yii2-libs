<?php
namespace winternet\yii2;

use yii\web\AssetBundle;

class FormHelperAsset extends AssetBundle {

	public $sourcePath = '@vendor/winternet-studio/yii2-libs/src/assets/FormHelper';

	// public $publishOptions = ['forceCopy' => true];

	public $basePath = '@webroot/assets';

	// public $css = ['nothingyet.css'];

	public $js = ['FormHelper.js'];

	public $depends = ['yii\web\JqueryAsset'];

}
