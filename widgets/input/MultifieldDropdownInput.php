<?php
namespace winternet\yii2\widgets\input;

use Yii;
use yii\widgets\InputWidget;
use yii\helpers\Html;

/**
 * Input widget for selecting values for multiple fields and store it in a single database field as JSON string.
 * 
 * The widget requires the attribute value to be an array and the form field is submitted as an array.
 * You may want to use the ArrayAttributesBehavior to handle array attributes.
 *
 * With model & with ActiveForm:
 * ```php
 * echo $form->field($model, 'show_fields')->widget(MultifieldDropdownInput::classname(), [
 * 	'items' =>
 *		['name' => 'address', 'label' => 'Address', 'list' => 
 *			[
 *				'req' => 'Required',
 *				'opt' => 'Optional',
 *				'hide' => 'Not shown',
 *			]
 *		],
 *		['name' => 'country', 'label' => 'Country', 'list' => 
 *			[
 *				'req' => 'Required',
 *				'opt' => 'Optional',
 *				'hide' => 'Not shown',
 *			]
 *		],
 *		['name' => 'phone', 'label' => 'Phone', 'list' => 
 *			[
 *				'req' => 'Required',
 *				'opt' => 'Optional',
 *				'hide' => 'Not shown',
 *			]
 *		],
 * ]);
 * ```
 * 
 * With model & without ActiveForm:
 * ```php
 * echo '<label class="control-label">Payment Terms</label>';
 * echo MultiLanguageInput::widget([
 * 	'model' => $model,
 * 	'attribute' => 'show_fields',
 * 	'items' => [...],
 * ]);
 * ```
 * 
 * Without model:
 * ```php
 * echo MultifieldDropdownInput::widget([
 * 	'name' => 'inputname',
 * 	'value' => ['address' => 'req', 'country' => 'req', 'phone' => 'opt'],
 * 	'items' => [...],
 * ]);
 * ```
 *
 * @author Allan Jensen, WinterNet Studio (www.winternet.no)
 **/

class MultifieldDropdownInput extends InputWidget {

	public $items = [];
	public $disabled = false;

	public $inputOptions = [];
	public $containerOptions = [];
	public $tableOptions = [];


	public function init() {
		parent::init();

		if (!isset($this->containerOptions['id'])) {
			$this->containerOptions['id'] = $this->getId();
		}

		if ($this->hasModel()) {
			$this->value = $this->model->{$this->attribute};
		}

		Html::addCssClass($this->containerOptions, 'multi-lang-input-widget');
		Html::addCssClass($this->inputOptions, 'form-control');
	}

	public function run() {
		$content = [];

		if ($this->disabled) {
			$this->inputOptions['disabled'] = true;
		}

		if ($this->hasModel()) {
			$value = $this->model->{$this->attribute};
		} else {
			$value = $this->value;
		}

		if (is_string($value)) {
			new \yii\base\Exception('Value for the MultifieldDropdownInput widget is a string. It must be an array. You may want to use the ArrayAttributesBehavior to handle array attributes.');
		}

		$basename = Html::getInputName($this->model, 'ev_fields');

		$content[] = Html::beginTag('table', $this->tableOptions);
		foreach ($this->items as $item) {
			$content[] = '<tr>';
			$content[] = '<td>'. Html::encode($item['label']) .'&nbsp;</td>';
			$content[] = '<td>'. Html::dropDownList($basename .'['. $item['name'] .']', $value[$item['name']], $item['list'], $this->inputOptions) .'</td>';
			$content[] = '</tr>';
		}
		$content[] = Html::endTag('table');

		return implode('', $content);
	}
}
