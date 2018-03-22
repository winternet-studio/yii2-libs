<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\crud\Generator */



// Load existing configurations
$allConfigs = @file_get_contents(Yii::getAlias('@runtime/storedGiiConfigs.json'));
if ($allConfigs) {
	$allConfigs = json_decode($allConfigs, true);
} else {
	$allConfigs = [];
	$allConfigs['_about'] = 'This file belongs to the Gii generator at '. get_class($generator) .' (https://github.com/winternet-studio/yii2-libs)';
}
if (!empty($allConfigs) && is_array($allConfigs['configs']['crud2'])) {
	// UI for selecting config to load
	echo '<div class="alert alert-info">';
	echo '<div style="margin-bottom: 10px">Load a previous configuration:</div>';
	foreach ($allConfigs['configs']['crud2'] as $cfgId => $currConfig) {
		echo '<a href="'. \yii\helpers\Url::current(['loadConfig' => $cfgId]) .'" class="btn btn-primary">'. $cfgId .'</a> ';
	}
	echo '</div>';
}
$loadConfig = Yii::$app->request->get('loadConfig');
if ($loadConfig && $allConfigs['configs']['crud2'][$loadConfig] && Yii::$app->request->isGet) {
	// Load a selected config
	foreach ($allConfigs['configs']['crud2'][$loadConfig] as $prop => $val) {
		$generator->{$prop} = $val;
	}
}



echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerClass');
echo $form->field($generator, 'viewPath');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'indexWidgetType')->dropDownList([
    'grid' => 'GridView',
    'list' => 'ListView',
]);
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'enablePjax')->checkbox();
echo $form->field($generator, 'messageCategory');




// Store the configuration
if ($generator->modelClass) {
	// generate the config
	$configId = $generator->modelClass;
	$allConfigs['configs']['crud2'][$configId] = $generator->toArray();

	// store to file
	file_put_contents(Yii::getAlias('@runtime/storedGiiConfigs.json'), json_encode($allConfigs, JSON_PRETTY_PRINT));
}
