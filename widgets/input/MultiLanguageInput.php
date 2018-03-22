<?php
namespace winternet\yii2\widgets\input;

use Yii;
use yii\widgets\InputWidget;
use yii\bootstrap\Html;
use winternet\yii2\Common;

/**
 * Multi-language input for being able to enter a text in multiplate languages and store as one string (so it's easy to store in a single database field)
 *
 * With model & with ActiveForm:
 * ```php
 * echo $form->field($model, 'payment_terms')->widget(MultiLanguageInput::class, [
 * 	'activeLanguages' => ['en', 'nb', 'da', 'de'],
 * ]);
 * ```
 * 
 * With model & without ActiveForm:
 * ```php
 * echo '<label class="control-label">Payment Terms</label>';
 * echo MultiLanguageInput::widget([
 * 	'model' => $model,
 * 	'attribute' => 'payment_terms',
 * 	'activeLanguages' => ['en', 'nb', 'da', 'de'],
 * ]);
 * ```
 * 
 * Without model:
 * ```php
 * echo MultiLanguageInput::widget([
 * 	'name' => 'inputname',
 * 	'value' => 'EN=No refunding after June 27.,,,DA=Ingen refundering efter 27. juni.',
 * 	'activeLanguages' => ['en', 'nb', 'da', 'de'],
 * ]);
 * ```
 *
 * @author Allan Jensen, WinterNet Studio (www.winternet.no)
 **/

class MultiLanguageInput extends InputWidget {

	public $inputType = 'input';  //input or textarea
	public $inputOptions = [];
	public $disabled = false;


	public $containerOptions = [];
	public $plainInputOptions = [];
	public $selectorOptions = [];
	public $addButtonOptions = [];

	public $addonSizeClass = 'input-sm';
	public $addonWidth = 90;

	public $activeLanguages = [];

	public $languages = [
		'af' => 'Afrikaans',
		'ar' => 'Arabic',
		'az' => 'Azerbaijani',
		'be' => 'Belarusian',
		'bg' => 'Bulgarian',
		'bn' => 'Bengali',
		'bs' => 'Bosnian',
		'ca' => 'Catalan',
		'cs' => 'Czech',
		'cy' => 'Welsh',
		'da' => 'Danish',
		'de' => 'German',
		'el' => 'Greek',
		'en' => 'English',
		'eo' => 'Esperanto',
		'es' => 'Spanish',
		'et' => 'Estonian',
		'eu' => 'Basque',
		'fa' => 'Persian',
		'fb' => 'Leet Speak',
		'fi' => 'Finnish',
		'fo' => 'Faroese',
		'fr' => 'French',
		'fy' => 'Frisian',
		'ga' => 'Irish',
		'gl' => 'Galician',
		'he' => 'Hebrew',
		'hi' => 'Hindi',
		'hr' => 'Croatian',
		'hu' => 'Hungarian',
		'hy' => 'Armenian',
		'id' => 'Indonesian',
		'is' => 'Icelandic',
		'it' => 'Italian',
		'ja' => 'Japanese',
		'ka' => 'Georgian',
		'km' => 'Khmer',
		'ko' => 'Korean',
		'ku' => 'Kurdish',
		'la' => 'Latin',
		'lt' => 'Lithuanian',
		'lv' => 'Latvian',
		'mk' => 'Macedonian',
		'ml' => 'Malayalam',
		'ms' => 'Malay',
		'nb' => 'Norwegian',
		'ne' => 'Nepali',
		'nl' => 'Dutch',
		'nn' => 'Norwegian (nynorsk)',
		'pa' => 'Punjabi',
		'pl' => 'Polish',
		'ps' => 'Pashto',
		'pt' => 'Portuguese',
		'ro' => 'Romanian',
		'ru' => 'Russian',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'sq' => 'Albanian',
		'sr' => 'Serbian',
		'sv' => 'Swedish',
		'sw' => 'Swahili',
		'ta' => 'Tamil',
		'te' => 'Telugu',
		'th' => 'Thai',
		'tl' => 'Filipino',
		'tr' => 'Turkish',
		'uk' => 'Ukrainian',
		'vi' => 'Vietnamese',
		'zh' => 'Chinese',
	];

	public $textOnlyOneTranslation = 'Please remove text from all fields except one.';
	public $textModalTitle = 'Information';
	public $textModalOKButton = 'OK';
	public $textEnable = 'Enable translations';
	public $textDisable = 'Disable translations';
	public $textTooltipMoveTop = 'Click to set as primary language';


	protected $alreadyExist = [];  //array of language codes for which we already have a translation


	public function init() {
		parent::init();

		if (!isset($this->containerOptions['id'])) {
			$this->containerOptions['id'] = $this->getId();
		}
		if ($this->options['id'] && $this->options['id'] == $this->containerOptions['id']) {
			$this->containerOptions['id'] = $this->containerOptions['id'] .'-noconflict';  //ensure we don't have conflicting IDs
		}

		if ($this->hasModel()) {
			$this->value = $this->model->{$this->attribute};
		}

		Html::addCssClass($this->containerOptions, 'multi-lang-input-widget');
		Html::addCssClass($this->inputOptions, 'form-control');
		Html::addCssClass($this->inputOptions, $this->containerOptions['id'] .'-lang-input');

		if (!$this->disabled) {
			$this->registerJs();
		}
	}

	public function run() {
		$content = [];


		if ($this->disabled) {
			$this->plainInputOptions['disabled'] = true;
			$this->inputOptions['disabled'] = true;
		}


		Html::addCssClass($this->options, $this->containerOptions['id'] .'-compiled-input');
		if ($this->hasModel()) {
			$content[] = Html::activeHiddenInput($this->model, $this->attribute, $this->options);
			$value = $this->model->{$this->attribute};
		} else {
			$content[] = Html::hiddenInput($this->name, $this->value, $this->options);
			$value = $this->value;
		}


		$parsed = Common::parseMultiLang($this->value, 'ALL');

		$useMultiLangMode = (is_array($parsed) ? true : false);
		Html::addCssClass($this->containerOptions, ($useMultiLangMode ? 'multi-lang-enabled' : 'multi-lang-disabled'));


		$content[] = Html::beginTag('div', $this->containerOptions);


		if ($useMultiLangMode) {
			Html::addCssStyle($this->plainInputOptions, 'display: none');
		}
		Html::addCssClass($this->plainInputOptions, 'form-control');
		$this->plainInputOptions['id'] = $this->containerOptions['id'] .'-plain';
		if ($this->inputType == 'textarea') {
			$content[] = Html::textarea( $this->containerOptions['id'] .'_plain', $value, array_merge($this->inputOptions, $this->plainInputOptions));
		} else {
			$content[] = Html::textInput($this->containerOptions['id'] .'_plain', $value, array_merge($this->inputOptions, $this->plainInputOptions));
		}

		$content[] = Html::beginTag('div', ['class' => 'language-inputs', 'style' => ($useMultiLangMode ? '' : 'display: none') ]);
		foreach ($this->activeLanguages as $currLang) {
			$languageName = $this->languages[$currLang];
			if (!$languageName) $languageName = strtoupper($currLang);

			$tmp  = '<div class="lang-input input-group" data-lang="'. Html::encode($currLang) .'"'. (!is_array($parsed) || $parsed[$currLang] === null ? ' style="display: none"' : '') .'>';
			$tmp .= '<span class="input-group-addon '. $this->addonSizeClass .'">';
			if ($this->disabled) {
				$tmp .= Html::encode($languageName);
			} else {
				$tmp .= '<a href="#" onclick="return false;" title="'. Html::encode($this->textTooltipMoveTop) .'" style="width: '. $this->addonWidth .'px; display: inline-block">'. Html::encode($languageName) .'</a>';
			}
			$tmp .= '</span>';
			if ($this->inputType == 'textarea') {
				$tmp .= Html::textarea( $this->containerOptions['id'] .'_input['. $currLang .']', (is_array($parsed) ? $parsed[$currLang] : null), $this->inputOptions);
			} else {
				$tmp .= Html::textInput($this->containerOptions['id'] .'_input['. $currLang .']', (is_array($parsed) ? $parsed[$currLang] : null), $this->inputOptions);
			}
			$tmp .= '</div>';

			$content[] = $tmp;

			if (is_array($parsed) && $parsed[$currLang] !== null) {
				$this->alreadyExist[] = $currLang;
			}
		}
		$content[] = Html::endTag('div');


		Html::addCssClass($this->selectorOptions, 'form-control');
		Html::addCssStyle($this->selectorOptions, 'width: auto; display: inline-block');
		Html::addCssClass($this->addButtonOptions, 'btn btn-success');
		Html::addCssStyle($this->addButtonOptions, 'width: auto; display: inline-block');

		if (!$this->disabled) {
			$content[] = '<div class="controls" style="text-align: right">';
			$content[] =  '<a href="#" onclick="return false;" class="disable-ml" '. (!$useMultiLangMode ? ' style="display: none"' : '') .'>'. Html::encode($this->textDisable) .'</a>';
			$content[] =  '<a href="#" onclick="return false;" class="enable-ml" ' . ( $useMultiLangMode ? ' style="display: none"' : '') .'>'. Html::encode($this->textEnable) .'</a>';
			$content[] =  '<span class="language-handler"'. ($useMultiLangMode ? '' : 'style="display: none"') .'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			$content[] =  Html::dropDownList($this->containerOptions['id'] .'_more_langs', null, $this->getLanguageList(), $this->selectorOptions);
			$content[] =  '&nbsp;';
			$content[] =  Html::button('Add', $this->addButtonOptions);
			$content[] =  '</span>';
			$content[] = '</div>';
		}


		$content[] = Html::endTag('div');

		return implode('', $content);
	}

	protected function registerJs() {
		$view = $this->getView();
		MultiLanguageInputAsset::register($view);

		$view->registerJs("multiLangInputWidget.init(". json_encode($this->containerOptions['id']) .", ". json_encode($this->inputType) .", {txtOnlyOneTransl: ". json_encode($this->textOnlyOneTranslation) .", txtModalTitle: ". json_encode($this->textModalTitle) .", txtOkButton: ". json_encode($this->textModalOKButton) ."});");
	}

	protected function getLanguageList() {
		$list = [];
		foreach ($this->activeLanguages as $lang) {
			if (!in_array($lang, $this->alreadyExist)) {
				$list[$lang] = ($this->languages[$lang] ? $this->languages[$lang] : $lang);
			}
		}
		return $list;
	}
}
