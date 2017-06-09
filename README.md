JsHelper
========
Helper for simplified handling of common tasks with AJAX, modals, and display of messages from Javascript code

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist winternet-studio/yii2-jshelper "*"
```

or add

```
"winternet-studio/yii2-jshelper": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :


```php
<?php
use winternet\jshelper\JsHelper;

echo JsHelper::initAjax();  //call within <body>
echo JsHelper::standardModal(['id' => 'StandardModal2']);
?>
```

Javascript code:

```js
// To make an AJAX call:

appJS.ajax({
	url: langUrl('/your/url'),
	type: 'POST',
	dataType: 'json',
	data: {var1: 'something', var2: 'something else'}
});

// To show a standard modal (either initAjax() or initModal() must have been called beforehand):

appJS.showModal('Here goes the <b>content</b> for your modal.');

// To show a modal, specifying more options:

appJS.showModal({
	title: 'Modal title goes here',
	html: 'Here goes the <b>content</b> for your modal.',
	customModalSelector: '#StandardModal2',
	openCallback: function(modalRef) {
		// code for before modal is being opened
		// NOTE: not reexecuted when a closed modal is shown again (set up standard Bootstrap modal events for that)
	},
	openedCallback: function(modalRef) {
		// code for after modal has been opened
		// NOTE: not reexecuted when a closed modal is shown again (set up standard Bootstrap modal events for that)
	},
	closeCallback: function(modalRef) {
		// code for before modal is being closed
		// NOTE: not reexecuted when a closed modal is shown again (set up standard Bootstrap modal events for that)
	},
	closedCallback: function(modalRef) {
		// code for after modal has been closed
		// NOTE: not reexecuted when a closed modal is shown again (set up standard Bootstrap modal events for that)
	}
});
```
