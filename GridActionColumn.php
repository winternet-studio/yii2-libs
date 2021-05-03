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
	 * {@inheritdoc}
	 */
	protected function initDefaultButton($name, $iconName, $additionalOptions = []) {
		// Add a unique identifier so we can target the button with CSS
		$additionalOptions['data-button-name'] = $name;

		if ($name === 'update') {
			if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
				$this->buttons[$name] = function ($url, $model, $key) use ($name, $iconName, $additionalOptions) {
					$options = array_merge([
						'aria-label' => $title,
						'data-pjax' => '0',
						'class' => 'btn btn-primary btn-xs',
					], $additionalOptions, $this->buttonOptions);
					return Html::a(Yii::t('app', 'Edit'), $url, $options);
				};
			}

		} else {
			return parent::initDefaultButton($name, $iconName, $additionalOptions);
		}
	}

}
