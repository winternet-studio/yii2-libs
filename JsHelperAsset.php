<?php
namespace winternet\yii2;

use yii\web\AssetBundle;

class JsHelperAsset extends AssetBundle {

	public $sourcePath = '@vendor/winternet-studio/yii2-libs/assets';

	// public $publishOptions = ['forceCopy' => true];

	public $basePath = '@webroot/assets';

	// public $css = ['nothingyet.css'];

	public $js = ['JsHelper.js?v=3'];

	public $depends = ['yii\web\JqueryAsset'];

}
