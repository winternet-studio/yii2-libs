<?php
namespace winternet\yii2\widgets\input;

use Yii;
use yii\widgets\InputWidget;
use yii\bootstrap\Html;

/**
 * Input widget for entering or selecting values for multiple fields and store it in a single database field as JSON string.
 * 
 * The widget requires the attribute value to be an array and the form field is submitted as an array.
 * You may want to use the ArrayAttributesBehavior to handle array attributes.
 *
 * Without a `type` parameter input field will be a normal single-line text field. The following types are possible:
 *	- `dropDownList` to make a dropdown box instead. Then the `list` parameter is required.
 *	- a callback function to make a custom widget. See example below.
 *
 * With model & with ActiveForm:
 * ```php
 * echo $form->field($model, 'show_fields')->widget(MultifieldInput::class, [
 * 	'items' =>
 *		[
 *			'name' => 'address',
 *			'label' => 'Address',
 *			'default' => 'opt',
 *		],
 *		[
 *			'name' => 'country',
 *			'label' => 'Country',
 *			'default' => 'opt',
 *			'type' => 'dropDownList',
 *			'list' => [
 *				'req' => 'Required',
 *				'opt' => 'Optional',
 *				'hide' => 'Not shown',
 *			]
 *		],
 *		[
 *			'name' => 'phone',
 *			'label' => 'Phone',
 *			'default' => 'opt',
 *			'type' => function($fieldName, $fieldValue, $inputOptions) {
 *				return \winternet\yii2\widgets\input\MultiLanguageInput::widget([
 *					'name' => $fieldName,
 *					'value' => $fieldValue,
 *					'inputOptions' => $inputOptions,
 *				]);
 *			},
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
 * echo MultifieldInput::widget([
 * 	'name' => 'inputname',
 * 	'value' => ['address' => 'req', 'country' => 'req', 'phone' => 'opt'],
 * 	'items' => [...],
 * ]);
 * ```
 *
 * @author Allan Jensen, WinterNet Studio (www.winternet.no)
 **/

class MultifieldInput extends InputWidget {

	/**
	 * @var array : Array of fields
	 */
	public $items = [];

	/**
	 * @var boolean : Encode the labels?
	 */
	public $encodeLabel = true;

	/**
	 * @var boolean : Encode the suffix (text written after input field)?
	 */
	public $encodeSuffix = true;

	/**
	 * @var boolean : Should input fields be disabled?
	 */
	public $disabled = false;


	public $inputOptions = [];

	public $containerOptions = [];

	public $tableOptions = [];


	public function init() {
		parent::init();

		if (!isset($this->containerOptions['id'])) {
			$this->containerOptions['id'] = Html::getInputId($this->model, $this->attribute);  //see comment below where we generated the tag for why we need this
			$this->containerOptions['data-widget-id'] = $this->getId();  //eg. w8
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
			new \yii\base\Exception('Value for the MultifieldInput widget is a string. It must be an array. You may want to use the ArrayAttributesBehavior to handle array attributes.');
		}

		$basename = Html::getInputName($this->model, ($this->hasModel() ? $this->attribute : $this->name));

		$content[] = Html::beginTag('div', $this->containerOptions);  //required because it has the ID of the input (and it seems like it must be a div according to findInput() in yii.activeForm.js - otherwise error message don't show)
		$content[] = Html::beginTag('table', $this->tableOptions);

		$counter = -1;
		foreach ($this->items as $item) {
			$counter++;
			$inputID = $this->containerOptions['id'] .'-'. $counter;

			$fieldName = $basename .'['. $item['name'] .']';
			$fieldValue = $value[$item['name']] ?? @$item['default'];

			$content[] = '<tr>';
			$content[] = '<td class="mfi-label">'. ($this->encodeLabel ? Html::encode($item['label']) : $item['label']) .'&nbsp;</td>';
			if (is_callable(@$item['type'])) {
				$content[] = '<td class="mfi-input custom-type">'. call_user_func($item['type'], $fieldName, $fieldValue, array_merge($this->inputOptions, ['id' => $inputID])) .'</td>';
			} elseif ($item['type'] == 'dropDownList') {
				$content[] = '<td class="mfi-input dropdown-type">'. Html::dropDownList($fieldName, $fieldValue, $item['list'], array_merge($this->inputOptions, ['id' => $inputID])) .'</td>';
			} else {
				$content[] = '<td class="mfi-input text-type">'. Html::textInput($fieldName, $fieldValue, array_merge($this->inputOptions, ['id' => $inputID])) .'</td>';
			}
			if (@$item['suffix']) {
				$content[] = '<td class="mfi-suffix">'. ($this->encodeSuffix ? Html::encode($item['suffix']) : $item['suffix']) .'</td>';
			}
			$content[] = '</tr>';
		}
		$content[] = Html::endTag('table');
		$content[] = Html::endTag('div');

		return implode('', $content);
	}
}
