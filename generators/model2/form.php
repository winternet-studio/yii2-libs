<?php

use yii\gii\generators\model\Generator;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\model\Generator */



// Load existing configurations
$allConfigs = @file_get_contents(Yii::getAlias('@runtime/storedGiiConfigs.json'));
if ($allConfigs) {
	$allConfigs = json_decode($allConfigs, true);
} else {
	$allConfigs = [];
	$allConfigs['_about'] = 'This file belongs to the Gii generator at '. get_class($generator) .' (https://github.com/winternet-studio/yii2-libs)';
}
if (!empty($allConfigs) && is_array($allConfigs['configs']['model2'])) {
	// UI for selecting config to load
	echo '<div class="alert alert-info">';
	echo '<div style="margin-bottom: 10px">Load a previous configuration:</div>';
	foreach ($allConfigs['configs']['model2'] as $cfgId => $currConfig) {
		echo '<a href="'. \yii\helpers\Url::current(['loadConfig' => $cfgId]) .'" class="btn btn-primary">'. $cfgId .'</a> ';
	}
	echo '</div>';
}
$loadConfig = Yii::$app->request->get('loadConfig');
if ($loadConfig && $allConfigs['configs']['model2'][$loadConfig] && Yii::$app->request->isGet) {
	// Load a selected config
	foreach ($allConfigs['configs']['model2'][$loadConfig] as $prop => $val) {
		$generator->{$prop} = $val;
	}
}



echo $form->field($generator, 'tableName')->textInput(['table_prefix' => $generator->getTablePrefix()]);
echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'ns');
echo $form->field($generator, 'baseClass');
echo $form->field($generator, 'db');
echo $form->field($generator, 'useTablePrefix')->checkbox();
echo $form->field($generator, 'generateRelations')->dropDownList([
    Generator::RELATIONS_NONE => 'No relations',
    Generator::RELATIONS_ALL => 'All relations',
    Generator::RELATIONS_ALL_INVERSE => 'All relations with inverse',
]);
echo $form->field($generator, 'generateRelationsFromCurrentSchema')->checkbox();
echo $form->field($generator, 'generateLabelsFromComments')->checkbox();
echo $form->field($generator, 'generateQuery')->checkbox();
echo $form->field($generator, 'queryNs');
echo $form->field($generator, 'queryClass');
echo $form->field($generator, 'queryBaseClass');
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
echo $form->field($generator, 'useSchemaName')->checkbox();




// Store the configuration
if ($generator->tableName) {
	// generate the config
	$configId = $generator->tableName;
	$allConfigs['configs']['model2'][$configId] = $generator->toArray();

	// store to file
	file_put_contents(Yii::getAlias('@runtime/storedGiiConfigs.json'), json_encode($allConfigs, JSON_PRETTY_PRINT));
}
