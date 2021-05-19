<?php
/**
 * This is the template for generating CRUD search class of the specified model.
 */

use yii\helpers\StringHelper;


/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
	$modelAlias = $modelClass . 'Model';
}
$rules = $generator->generateSearchRules();
$labels = $generator->generateSearchLabels();
$searchAttributes = $generator->getSearchAttributes();
$searchConditions = $generator->generateSearchConditions();


// Add rules for operators
$operatorAttributes = [];
foreach ($generator->getSearchAttributes() as $attribute) {
	$operatorAttributes[] = $attribute .'_OP';
}
$rules[] = "[['" . implode("', '", $operatorAttributes) . "'], 'string']";
$rules[] = "[['" . implode("', '", $operatorAttributes) . "'], 'in', 'range' => array_keys(\\winternet\\yii2\\DatabaseHelper::compareOperators())]";

// Add rule for common search term
$rules[] = "['__common', 'string']";

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->searchModelClass, '\\')) ?>;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use <?= ltrim($generator->modelClass, '\\') . (isset($modelAlias) ? " as $modelAlias" : "") ?>;

/**
 * <?= $searchModelClass ?> represents the model behind the search form about `<?= $generator->modelClass ?>`.
 */
class <?= $searchModelClass ?> extends <?= isset($modelAlias) ? $modelAlias : $modelClass ?> {

	/**
	 * @var string Attribute for term to search for across all attributes
	 */
	public $__common;

	/**
	 * @var string Attributes for comparison operator for each attribute
	 */
<?php
	foreach ($operatorAttributes as $operatorAttribute) {
?>
	public $<?= $operatorAttribute ?>;
<?php
	}
?>

	protected $callingViewable = false;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			<?= implode(",\n			", $rules) ?>,
		];
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios() {
		$scenarios = parent::scenarios();

		// To avoid infinite loop in case parent viewable() is dependent on scenarios() just returns the basic scenario while we call viewable() below
		if ($this->callingViewable) {
			return $scenarios;
		}

		$this->callingViewable = true;
		$viewable = parent::viewable();
		$this->callingViewable = false;

		// Add the *_OP attributes for the current scenario
		$scenarios[$this->scenario] = [];  //start over because we need to include those that we have view permission for without having update permission (and remove "!" in front of any attributes)
		foreach ($viewable as $attribute) {
			$scenarios[$this->scenario][] = $attribute;
			$scenarios[$this->scenario][] = $attribute .'_OP';
		}
		$scenarios[$this->scenario][] = '__common';
		return $scenarios;

		// Do not bypass scenarios since we need to limit searchable fields based on user's permissions
		// bypass scenarios() implementation in the parent class
		// return Model::scenarios();
	}

	/**
	 * Attributes to allow searching by (outcommenting attributes disables searching for ALL users)
	 *
	 * @return array
	 */
	public function searchable() {
		return [
			'<?= implode("',\n			'", $generator->getSearchAttributes()) ?>',
		];
	}

	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params
	 *
	 * @return ActiveDataProvider
	 */
	public function search($params) {
		// Handle memorizing the last used search parameters
		$shortModelName = \yii\helpers\StringHelper::basename(__CLASS__);
		if (Yii::$app->session && !Yii::$app->request->isConsoleRequest) {
			Yii::$app->session->open();
			if (empty($params[$shortModelName])) {
				if (!empty($_SESSION['cache'][__CLASS__]['searchParams'])) {
					$params[$shortModelName] = $_SESSION['cache'][__CLASS__]['searchParams'];
				}
			} else {
				$_SESSION['cache'][__CLASS__]['searchParams'] = $params[$shortModelName];
			}
		}

		$this->applyUserScenario();

		$searchable = array_intersect($this->searchable(), $this->viewable());

		$query = <?= isset($modelAlias) ? $modelAlias : $modelClass ?>::find();
		<?= isset($modelAlias) ? $modelAlias : $modelClass ?>::applyUserConditions($query);

		// add more conditions that should always apply here

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			// 'sort' => ['defaultOrder' => ['<?= $generator->getSearchAttributes()[0] ?>' => SORT_ASC]],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// outcomment the following line if you want to return records when validation fails
			$query->where('0=1');
			return $dataProvider;
		}

		// Field specific search
		foreach ($searchable as $attribute) {
			$operator = $this->{$attribute .'_OP'};
			if (!$operator) $operator = 'contains';  //use `contains` for the purpose of GridView filter which doesn't provide an operator

			if (in_array($operator, ['empty', 'notempty'])) {
				$query->andWhere(\winternet\yii2\DatabaseHelper::modelToCondition($attribute, $this->$attribute, $operator));
			} else {
				$query->andFilterWhere(\winternet\yii2\DatabaseHelper::modelToCondition($attribute, $this->$attribute, $operator));
			}
		}

		// Search across all fields
		$condition = ['or'];
		foreach ($searchable as $attribute) {
			$condition[] = ['like', $attribute, $this->__common];
		}
		$query->andFilterWhere($condition);

		return $dataProvider;
	}
}
