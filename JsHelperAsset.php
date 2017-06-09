<?php
namespace winternet\yii2;

use yii\web\AssetBundle;

class JsHelperAsset extends AssetBundle {

	public $sourcePath = '@vendor/winternet-studio/yii2-libraries/assets';
	public $basePath = '@webroot/assets';
	// public $css = ['nothingyet.css'];
	public $js = ['jshelper.js?v=1'];
	public $depends = ['yii\web\JqueryAsset'];

}
