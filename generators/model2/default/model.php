<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

echo "<?php\n";
?>
namespace <?= $generator->ns ?>;

use Yii;

/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($tableSchema->columns as $column): ?>
 * @property <?= "{$column->phpType} \${$column->name}\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') ?> {
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return '<?= $generator->generateTableName($tableName) ?>';
	}
<?php if ($generator->db !== 'db'): ?>

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb() {
		return Yii::$app->get('<?= $generator->db ?>');
	}
<?php endif; ?>

	/**
	 * @inheritdoc
	 */
	public function rules() {
		// $options = self::allowedValues();

		return [<?= "\n            " . implode(",\n            ", $rules) . ",\n        " ?>];
	}

	/**
	 * @inheritdoc
	 */
	// public function scenarios() {
	// 	$scenarios = parent::scenarios();

	// 	$all_attributes = $this->attributes();
	// 	$scenarios[self::SCENARIO_ADMIN] = $all_attributes;

	// 	return $scenarios;
	// }

	/**
	 * Return an array with list of attributes that should be visible (= user allowed to see) for the current scenario
	 *
	 * @return array
	 */
	// public function viewable() {
	// 	$scenario = $this->getScenario();

 	// 	if ($scenario == self::SCENARIO_DEFAULT) {
 	// 		return [];

 	// 	} elseif ($scenario == self::SCENARIO_ADMIN) {
 	// 		$attributes = $this->attributes();
 	// 		return $attributes;

 	// 	} else {
 	// 		return [];

 	// 	}
	// }

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			[
				'class' => \winternet\yii2\behaviors\CleanupBehavior::className(),
			],
		];
	}

	/**
	 * Return allowed values for given attributes
	 */
	// public static function allowedValues($attribute = null) {
	// 	$values = [
	// 		'attributeName' => [
	// 			0 => 'No',
	// 			1 => 'Yes',
	// 		],
	// 	];
	// 	if ($attribute !== null) { return $values[$attribute]; } else { return $values; }
	// }

	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [
<?php foreach ($labels as $name => $label): ?>
			<?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
		];
	}
<?php foreach ($relations as $name => $relation): ?>

	/**
	 * @return array
	 */
	public function attributeHints() {
		return [
<?php foreach ($labels as $name => $label): ?>
			<?= "'$name' => '',\n" ?>
<?php endforeach; ?>
		];
	}

	// Custom setters/getters
	// ...

	// Relationships

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function get<?= $name ?>() {
		<?= $relation[0] . "\n" ?>
	}
<?php endforeach; ?>
<?php if ($queryClassName): ?>
<?php
	$queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
	echo "\n";
?>
	/**
	 * @inheritdoc
	 * @return <?= $queryClassFullName ?> the active query used by this AR class.
	 */
	public static function find() {
		return new <?= $queryClassFullName ?>(get_called_class());
	}
<?php endif; ?>

	// public static function findUsers<?= $className ?>s($id = false, $options = []) {
	// 	if (Yii::$app->user->isGuest) {
	// 		return [];
	// 	}

	// 	$query = self::find();
	// 	self::applyUserConditions($query);

	// 	if ($id) {
	// 		//return one
	// 		$query->andWhere(['eventID' => $id]);
	// 		$model = $query->one();

	// 		if ($model && $options['setScenario'] === true) {
	// 			$model->applyUserScenario();
	// 		}

	// 		return $model;
	// 	} else {
	// 		//return all
	// 		$query->indexBy('<?= array_keys($labels)[0];  //assume first field is primary key ?>');

	// 		$models = $query->all();

	// 		return $models;
	// 	}
	// }

	// public function applyUserScenario($options = []) {
	// 	if (0) {
	// 		$this->setScenario(self::SCENARIO_DEFAULT);
	// 	} else {
	// 		new \app\components\Error('Do not know how to set user scenario.', ['User' => Yii::$app->user->identity->userID]);
	// 	}
	// 	return $this;
	// }

	// public static function applyUserConditions(&$query) {
	// 	if (Yii::$app->user->isGuest) {
	// 		$query->where('0=1');
	// 	} else {

	// 		if ($accessLevelOne) {
	// 			$query->andWhere(['in', '<?= array_keys($labels)[0] ?>', [1, 2, 3] ]);
	// 		} elseif ($accessLevelTwo) {
	// 			$query->andWhere(['in', '<?= array_keys($labels)[0] ?>', [6, 7, 8] ]);
	// 		} else {
	// 			$query->andWhere(['<?= array_keys($labels)[0] ?>' => -9999]);
	// 		}

	// 	}
	// }

	// Other functions
	// ...
}
