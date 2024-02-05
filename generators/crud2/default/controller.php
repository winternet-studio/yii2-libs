<?php
/**
 * This is the template for generating a CRUD controller class file.
 */

use yii\db\ActiveRecordInterface;
use yii\helpers\StringHelper;


/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$controllerClass = StringHelper::basename($generator->controllerClass);
$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
	$searchModelAlias = $searchModelClass . 'Search';
}

/* @var $class ActiveRecordInterface */
$class = $generator->modelClass;
$pks = $class::primaryKey();
$urlParams = $generator->generateUrlParams();
$actionParams = $generator->generateActionParams();
$actionParamComments = $generator->generateActionParamComments();

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->controllerClass, '\\')) ?>;

use Yii;
use <?= ltrim($generator->modelClass, '\\') ?>;
<?php if (!empty($generator->searchModelClass)): ?>
use <?= ltrim($generator->searchModelClass, '\\') . (isset($searchModelAlias) ? " as $searchModelAlias" : "") ?>;
<?php else: ?>
use yii\data\ActiveDataProvider;
<?php endif; ?>
use <?= ltrim($generator->baseControllerClass, '\\') ?>;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * <?= $controllerClass ?> implements the CRUD actions for <?= $modelClass ?> model.
 */
class <?= $controllerClass ?> extends <?= StringHelper::basename($generator->baseControllerClass) ?> {
	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'delete' => ['POST'],
				],
			],
		];
	}

	/**
	 * Lists all <?= $modelClass ?> models.
	 * @return mixed
	 */
	public function actionIndex() {
<?php if (!empty($generator->searchModelClass)): ?>
		$searchModel = new <?= isset($searchModelAlias) ? $searchModelAlias : $searchModelClass ?>();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		$filterApplied = (empty($dataProvider->query->where) ? false : true);

		if (@Yii::$app->params['isApi']) {
			return ['result' => $dataProvider->getModels() ];
		} else {
			return $this->render('index', [
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider,
				'filterApplied' => $filterApplied,
			]);
		}
<?php else: ?>
		$dataProvider = new ActiveDataProvider([
			'query' => <?= $modelClass ?>::find(),
		]);
		$filterApplied = (empty($dataProvider->query->where) ? false : true);

		if (@Yii::$app->params['isApi']) {
			return ['result' => $dataProvider->getModels() ];
		} else {
			return $this->render('index', [
				'dataProvider' => $dataProvider,
				'filterApplied' => $filterApplied,
			]);
		}
<?php endif; ?>
	}

	/**
	 * Displays a single <?= $modelClass ?> model.
	 * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
	 * @return mixed
	 */
	public function actionView(<?= $actionParams ?>) {
		if (@Yii::$app->params['isApi']) {
			return ['result' => $this->findModel($id) ];
		} else {
			return $this->render('view', [
				'model' => $this->findModel(<?= $actionParams ?>),
			]);
		}
	}

	/**
	 * Creates a new <?= $modelClass ?> model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate() {
		$model = new <?= $modelClass ?>();
		$model->applyUserScenario();

		// Form submission
		if ((Yii::$app->request->isAjax && !Yii::$app->request->isPjax) || @Yii::$app->params['isApi']) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			$model->load(Yii::$app->request->post());
			if (@$_POST['ajax']) {
				$model->validate();  //don't save when AJAX validation is done due to enableAjaxValidation=true
			} else {
				$model->save();
			}
			$result = \winternet\yii2\FormHelper::addModelResult(null, $model, ['forActiveForm' => true]);
			return $result;
		}

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(['view', <?= $urlParams ?>]);
		} else {
			return $this->render('create', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Updates an existing <?= $modelClass ?> model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
	 * @return mixed
	 */
	public function actionUpdate(<?= $actionParams ?>) {
		$model = $this->findModel(<?= $actionParams ?>);

		// Form submission
		if ((Yii::$app->request->isAjax && !Yii::$app->request->isPjax) || @Yii::$app->params['isApi']) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			$model->load(Yii::$app->request->post());
			if (@$_POST['ajax']) {
				$model->validate();  //don't save when AJAX validation is done due to enableAjaxValidation=true
			} else {
				$model->save();
			}
			$result = \winternet\yii2\FormHelper::addModelResult(null, $model, ['forActiveForm' => true]);
			return $result;
		}

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(['view', <?= $urlParams ?>]);
		} else {
			return $this->render('update', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Deletes an existing <?= $modelClass ?> model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
	 * @return mixed
	 */
	public function actionDelete(<?= $actionParams ?>) {
		$result = new \winternet\yii2\Result();

		$model = $this->findModel(<?= $actionParams ?>);
		if (!$model->delete()) {
			if (@Yii::$app->params['isApi']) {
				$result->addErrors($model->getErrors());
			} else {
				\Yii::$app->system->error('Cannot delete record because: '. implode(' ', $model->getErrorSummary(true)), ['Errors' => $model->getErrors(), 'Model' => $model->toArray() ], ['expire' => 2]);
			}
		}

		if (@Yii::$app->params['isApi']) {
			return $result->response();
		} else {
			return $this->redirect(['index']);
		}
	}

	/**
	 * Finds the <?= $modelClass ?> model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
	 * @return <?=                   $modelClass ?> the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel(<?= $actionParams ?>) {
<?php
if (count($pks) === 1) {
	$condition = '$id';
} else {
	$condition = [];
	foreach ($pks as $pk) {
		$condition[] = "'$pk' => \$$pk";
	}
	$condition = '[' . implode(', ', $condition) . ']';
}
?>
		if (($model = <?= $modelClass ?>::findOfUser(<?= $condition ?>, ['setScenario' => true])) !== null) {
			return $model;
		}
		throw new NotFoundHttpException('The requested page does not exist.');
	}
}
