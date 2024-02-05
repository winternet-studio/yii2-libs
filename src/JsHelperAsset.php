<?php
namespace winternet\yii2;

use yii\web\AssetBundle;

class JsHelperAsset extends AssetBundle {

	public $sourcePath = '@vendor/winternet-studio/yii2-libs/src/assets/JsHelper';

	// public $publishOptions = ['forceCopy' => true];

	public $basePath = '@webroot/assets';

	// public $css = ['nothingyet.css'];

	public $js = ['JsHelper.js'];

	public $depends = ['yii\web\JqueryAsset'];

}
