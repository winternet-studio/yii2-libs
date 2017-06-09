<?php
namespace winternet\jshelper;

use yii\web\AssetBundle;

class JsHelperAsset extends AssetBundle {

	public $sourcePath = '@vendor/winternet/yii2-jshelper/assets';
	public $basePath = '@webroot/assets';
	// public $css = ['nothingyet.css'];
	public $js = ['jshelper.js?v=1'];
	public $depends = ['yii\web\JqueryAsset'];

}
