<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class SystemDocumentation extends Component {

	/**
	 * Generate overview of attributes permissions (add, edit and view) for a model
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
.mark-invisible, .mark-visible, .mark-validated, .mark-not-validated {
	position: relative;
}
.mark-invisible:after, .mark-visible:after, .mark-validated:after, .mark-not-validated:after {
	content: "";
	position: absolute;
	right: 0;
	width: 0; 
	height: 0; 
	display: block;
}
.mark-invisible:after {
	top: 0;
	border-left: 8px solid transparent;
	border-bottom: 8px solid transparent;
	border-top: 8px solid #f00;
}
.mark-visible:after {
	top: 0;
	border-left: 8px solid transparent;
	border-bottom: 8px solid transparent;
	border-top: 8px solid #c6e2bb;
}
.mark-validated:after {
	bottom: 0;
	border-left: 4px solid transparent;
	border-top: 4px solid transparent;
	border-bottom: 4px solid #ace0aa;
}
.mark-not-validated:after {
	bottom: 0;
	border-left: 4px solid transparent;
	border-top: 4px solid transparent;
	border-bottom: 4px solid #e0aaaa;
}');
?>
<div>Blank green = allow massive assignment both when adding and editing record (= the user may directly specify these values)</div>
<div>Red background = never allow massive assignment (note that direct assignment in code is always allowed)</div>
<div>Red triangle upper/right = never allow viewing</div>
<div>Green triangle lower/right = attribute is validated</div>
<div>Red triangle lower/right = attribute is not validated</div>

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

				$isValidated = (in_array($attribute, $activeUpdateAttributes) || in_array('!'.$attribute, $activeUpdateAttributes) ? true : false);   // http://www.yiiframework.com/doc-2.0/yii-base-model.html#scenarios%28%29-detail
				$validatedClass = ($isValidated ? ' mark-validated' : ' mark-not-validated');  

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
	<td class="danger<?= $invisibleClass . $validatedClass ?>"></td>
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
