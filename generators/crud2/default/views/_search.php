<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */
/* @var $form yii\widgets\ActiveForm */

echo "<?php\n";

// TODO: are there any changes I need to make to the search controller? (c:\www-root\github-clones\yii2-libs\generators\crud2\default\controller.php)
?>
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->searchModelClass, '\\') ?> */
/* @var $form yii\widgets\ActiveForm */

$this->registerJs(<<<'JS'
function toggleSearchMethod() {
	$('.booking-search, .booking-simple-search').slideToggle({complete: function() {
		// Clear fields we are hiding and reset operator
		$('.crud-search-area :input').not(':visible').not('.operator-input').val('');
		$('.crud-search-area select.operator-input').find('option').prop('selected', function () {
	        return $(this).prop('defaultSelected');
	    }).parent().trigger('change');
	}});
}
$(function() {
	$('.crud-search-area select.operator-input').on('change', function(ev) {
		// Show operator help
		$(ev.target).closest('.row').find('.operator-hint').html(  $(ev.target).find('option:selected').attr('title')  );
	})
});
JS
, $this::POS_END);
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-search crud-search-area">
<?= "<?php\n" ?>$operatorDropdownOptions = \winternet\yii2\DatabaseHelper::operatorHints();
$attributeTypes = \winternet\yii2\ModelHelper::getAttributeTypes($model);

$form = ActiveForm::begin([
	'action' => ['index'],
	'method' => 'get',
	'fieldConfig' => function($model, $attribute) use (&$operatorDropdownOptions, &$attributeTypes) {
		if ($attribute == '__common') return [];
		$defaultOperator = $model->{$attribute .'_OP'};
		if (!$defaultOperator) {
			if ($attributeTypes[$attribute]['common'] == 'numeric') {
				$defaultOperator = 'equal';
			} else {
				$defaultOperator = 'contains';
			}
		}
		$template  = '<div class="row">';
		$template .=  '<div class="col-sm-2 text-right">{label}</div>';
		$template .=  '<div class="col-sm-2">'. Html::dropDownList(Html::getInputName($model, $attribute .'_OP'), $defaultOperator, $model->compareOperators(), [
			'class' => 'form-control operator-input',
			'options' => $operatorDropdownOptions,
		]) .'</div>';
		$template .=  '<div class="col-sm-8">{input}{hint}{error}<div class="operator-hint">'. $operatorDropdownOptions[ $model->{$attribute .'_OP'} ]['title'] /*needed if default operator has a hint*/ .'</div></div>';
		$template .= '</div>';
		return [
			'template' => $template,
		];
	},
]);
?>

	<div class="search-options clearfix">
		<a href="#" onclick="toggleSearchMethod();return false;" class="btn btn-xs btn-primary pull-right show-adv-search">Toggle advanced search</a>
	</div>
	<div style="height: 10px"></div>

	<div class="booking-simple-search">
		<div class="input-group">
			<?= "<?= " ?>$form->field($model, '__common', ['template' => '{input}'])->label(false) ?>
			<span class="input-group-btn">
				<?= "<?= " ?>Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
			</span>
		</div>
	</div>

	<div class="booking-search collapse">
		<div class="panel panel-info">
			<div class="panel-heading"><h4><?= "<?= " ?>Yii::t('app', 'Advanced search') ?></h4></div>
			<div class="panel-footer">
<?= "<?php\n" ?>
<?php
$count = 0;
foreach ($generator->getColumnNames() as $attribute) {
	if (++$count < 6 || true) {
		echo "echo " . $generator->generateActiveSearchField($attribute) . ";\n";
	} else {
		echo "// echo " . $generator->generateActiveSearchField($attribute) . ";\n";
	}
}
?>
?>
				<div class="form-group">
					<?= "<?= " ?>Html::submitButton(<?= $generator->generateString('Search') ?>, ['class' => 'btn btn-primary']) ?>
					<?= "<?= " ?>Html::resetButton(<?= $generator->generateString('Reset') ?>, ['class' => 'btn btn-default']) ?>
				</div>
			</div>
		</div>

<?= "<?php " ?>ActiveForm::end(); ?>

	</div>
</div>

<div style="height: 40px"></div>
