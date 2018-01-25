<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class SystemDocumentation extends Component {

	/**
	 * Generate overview of attributes permissions (add, edit and view) for a model
	 *
	 * TODO: consider if scenario attribute has been prefixed with "!" to indicate no massive assignment - but still should be validated (see http://www.yiiframework.com/doc-2.0/yii-base-model.html#scenarios%28%29-detail)
	 *
	 * @param yii\base\model|string $model : Actual model or fully qualified name of model to show permissions for
	 */
	public static function showAttributePermissions($model) {
		if (is_string($model)) {
			$modelInsert = new $model();
			$modelUpdate = new $model();
		} else {
			$className = $model->className();
			$modelInsert = new $className();
			$modelUpdate = new $className();
		}
		$modelUpdate->isNewRecord = false;

		$allAttributes = $modelInsert->attributes();
		$scenariosInsert = $modelInsert->scenarios();
		$scenariosUpdate = $modelUpdate->scenarios();

		$viewableAttributes = [];


		ob_start();

		Yii::$app->controller->getView()->registerCss('
.mark-invisible, .mark-visible {
	position: relative;
}
.mark-invisible:after, .mark-visible:after {
	content: "";
	position: absolute;
	top: 0;
	right: 0;
	width: 0; 
	height: 0; 
	display: block;
	border-left: 8px solid transparent;
	border-bottom: 8px solid transparent;
}
.mark-invisible:after {
	border-top: 8px solid #f00;
}
.mark-visible:after {
	border-top: 8px solid #c6e2bb;
}');
?>
<div>Blank green = allow massive assignment both when adding and editing record (= the user may directly specify these values)</div>
<div>Red background = never allow massive assignment (note that direct assignment in code is always allowed)</div>
<div>Red triangle = never allow viewing</div>

<h3><?= $modelInsert->className() ?></h3>
<table class="table table-bordered table-condensed bs-auto-width">
<tr>
	<th class="info">Scenario</th>
<?php
		foreach ($scenariosInsert as $scenario => $activeInsertAttributes) {
			if ($scenario == 'default') continue;
?>
	<th class="info"><?= strtoupper(str_replace('_', ' ', $scenario)) ?></th>
<?php
			try {
				$modelUpdate->setScenario($scenario);
				$viewableAttributes[$scenario] = $modelUpdate->viewable();
			} catch (\Exception $e) {
				$viewableAttributes = null;
			}
		}
?>
</tr>
<?php
		foreach ($allAttributes as $attribute) {
?>
<tr>
	<td class="info"><?= $attribute ?></td>
<?php
			foreach ($scenariosInsert as $scenario => $activeInsertAttributes) {
				if ($scenario == 'default') continue;

				$activeUpdateAttributes = $scenariosUpdate[$scenario];

				$isViewable = ($viewableAttributes === null || in_array($attribute, $viewableAttributes[$scenario]) ? true : false);
				$invisibleClass = ($isViewable ? '' : ' mark-invisible');

				if (in_array($attribute, $activeInsertAttributes) && in_array($attribute, $activeUpdateAttributes)) {
?>
	<td class="success<?= $invisibleClass ?>"></td>
<?php
				} elseif (in_array($attribute, $activeInsertAttributes)) {
?>
	<td class="success<?= $invisibleClass ?> text-center"><span style="color: #ff8686">No UPDATE</span></td>
<?php
				} elseif (in_array($attribute, $activeUpdateAttributes)) {
?>
	<td class="success<?= $invisibleClass ?> text-center"><span style="color: #ff8686">No INSERT</span></td>
<?php
				} else {
?>
	<td class="danger<?= $invisibleClass ?>"></td>
<?php
				}
			}
?>
</tr>
<?php
		}
?>
</table>
<?php
		return ob_get_clean();
	}
}
