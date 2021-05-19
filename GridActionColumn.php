<?php
namespace winternet\yii2;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * Customization of the default ActionColumn by making a bigger edit button (with text)
 */
class GridActionColumn extends \yii\grid\ActionColumn {

	/**
	 * {@inheritdoc}
	 */
	public $template = '<div class="grid-action-column">{update} {view} {delete}</div>';

	/**
	 * @var array : Use label on a button instead of a symbol. By default the update button will have the label "Edit" - other buttons will still use a symbol.
	 */
	public $buttonNames = [
		'update' => 'Edit',
		'view' => null,
		'delete' => null,
		'history' => null,
	];

	/**
	 * @var array : Set to an array to add a history button. The array can have these keys:
	 *   - `controller` : (opt.) Name of controller to link to
	 *   - `action` : (req.) Name of action to link to
	 *   - `secretSalt` : (req.) Secret salt used for generating a hash in the generated URL
	 */
	public $historyButton = null;

	public function init() {
		parent::init();

		if (!empty($this->historyButton)) {
			// Add to template if not already present
			if (strpos($this->template, '{history}') === false) {
				$lastBracket = strrpos($this->template, '}');
				if ($lastBracket !== false) {
					$this->template = substr_replace($this->template, '} {history}', $lastBracket, strlen('}'));
				}
			}

			$this->initDefaultButton('history', 'time');
		}
	}

	public function createUrl($action, $model, $key, $index) {
		if ($action === 'history') {
			$className = get_class($model);
			$timestamp = time();
			$params = is_array($key) ? $key : ['id' => (string) $key, 'model' => $className, 'timestamp' => $timestamp, 'hash' => hash('sha256', $className . $key .'-'. $timestamp . $this->historyButton['secretSalt'])];
			$params[0] = $this->historyButton['controller'] ? $this->historyButton['controller'] . '/' . $this->historyButton['action'] : $this->historyButton['action'];

			return \yii\helpers\Url::toRoute($params);
		} else {
			return parent::createUrl($action, $model, $key, $index);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function initDefaultButton($name, $iconName, $additionalOptions = []) {
		// Add a unique identifier so we can target the button with CSS
		$additionalOptions['data-button-name'] = $name;

		if ($this->buttonNames[$name]) {
			if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
				$this->buttons[$name] = function ($url, $model, $key) use ($name, $iconName, $additionalOptions) {
					switch ($name) {
						case 'view':
							$title = Yii::t('yii', 'View');
							break;
						case 'update':
							$title = Yii::t('yii', 'Update');
							break;
						case 'delete':
							$title = Yii::t('yii', 'Delete');
							break;
						default:
							$title = ucfirst($name);
					}
					$options = array_merge([
						'aria-label' => $title,
						'data-pjax' => '0',
						'class' => 'btn btn-primary btn-xs',
					], $additionalOptions, $this->buttonOptions);
					return Html::a(Yii::t('yii', $this->buttonNames[$name]), $url, $options);
				};
			}

		} else {
			return parent::initDefaultButton($name, $iconName, $additionalOptions);
		}
	}

}
