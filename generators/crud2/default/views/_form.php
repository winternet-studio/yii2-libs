<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

/* @var $model \yii\db\ActiveRecord */
$model = new $generator->modelClass();
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
	$safeAttributes = $model->attributes();
}

echo "<?php\n";
?>
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use winternet\yii2\ModelHelper;
use winternet\yii2\FormHelper;
use demogorgorn\ajax\AjaxSubmitButton;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form">

<?= "<?php\n" ?>
$form = ActiveForm::begin([
	'enableAjaxValidation' => true,
]);

FormHelper::WarnLeavingUnsaved($this, $form);

$editAttribs = $model->safeAttributes();
$viewAttribs = $model->viewable();
$hints = $model->attributeHints();
$this->registerCss(ModelHelper::requiredAttributesCss($model));


AjaxSubmitButton::begin([
	'label' => ($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update')),
	'ajaxOptions' => [
		'type' => 'POST',
		'url' => Url::current(),
		'beforeSend' => new \yii\web\JsExpression("function() { appJS.showProgressBar(); }"),
		'success' => \winternet\yii2\FormHelper::processAjaxSubmit([
			'form' => $form,
			'view' => $this,
			'on_successJS' => "wsYii2.FormHelper.WarnLeavingUnsaved.markSaved('{currentForm}'); location.href = '/'; /* TODO: set location */",
			'on_completeJS' => "appJS.hideProgressBar();",
		]),
		'error' => \winternet\yii2\FormHelper::processAjaxSubmitError(['jsBefore' => "appJS.hideProgressBar();"]),
	],
	'options' => ['class' => 'btn btn-primary', 'type' => 'submit'],
]);
AjaxSubmitButton::end();


<?php
foreach ($generator->getColumnNames() as $attribute) {
	if (in_array($attribute, $safeAttributes)) {
		$activeField = $generator->generateActiveField($attribute);

		if (strpos($activeField, 'checkboxList') !== false) {
			if (preg_match("/]\\)$/", $activeField)) {
				$activeField = substr($activeField, 0, -2) .", 'itemOptions' => ['disabled' => !in_array('". $attribute ."', \$editAttribs)] ". substr($activeField, -2);
			} else {
				$activeField = substr($activeField, 0, -1) ."['itemOptions' => ['disabled' => !in_array('". $attribute ."', \$editAttribs)] ]". substr($activeField, -1);
			}
		} else {
			if (preg_match("/]\\)$/", $activeField)) {
				$activeField = substr($activeField, 0, -2) .", 'disabled' => !in_array('". $attribute ."', \$editAttribs) ". substr($activeField, -2);
			} else {
				$activeField = substr($activeField, 0, -1) ."['disabled' => !in_array('". $attribute ."', \$editAttribs) ]". substr($activeField, -1);
			}
		}

		echo "\n";
		echo "if (in_array('". $attribute ."', \$viewAttribs)) {\n";
		echo "	echo " . $activeField . "\n";   //";\n";
		echo "		->hint(@\$hints['". $attribute ."']);\n";
		echo "}\n";
	}
} ?>
?>
<?php
if (0) {  //we use the AjaxSubmitButton in the top instead
?>
	<div class="form-group">
		<?= "<?= " ?>Html::submitButton($model->isNewRecord ? <?= $generator->generateString('Create') ?> : <?= $generator->generateString('Update') ?>, ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	</div>
<?php
}
?>

<?= "<?php " ?>ActiveForm::end(); ?>

</div>
