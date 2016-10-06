# Yii2 customizable ErrorHandler

Can be customized in terms of which error types should be converted to exceptions or only logged.

## Installation

```bash
php composer.phar require cronfy/yii2-web-errorhandler
```

## Usage

Replace ```errorHandler``` component in application configuration and configure error types you want to convert to exceptions
or log. Default is ```E_ALL | E_STRICT```.

Example:

```php
...
    'components' => [
        'errorHandler' => [
            'class' => 'cronfy\yii\web\ErrorHandler',
            'typesToExceptions' => YII_DEBUG ? (E_ALL | E_STRICT) : false,
            'typesToLog' => E_ALL | E_STRICT,
        ],
    ],
...
```

This configuration will convert all php notices and warnings to exceptions only in debug mode, and none in production environment.
Errors will be logged both in debug and production modes. You can customize error types that go to log or convereted to exceptions.

Errors are logged via internal Yii2 ```log``` component.

You can enable/disable ErrorHandler for particular error types by setting ```typesToHandle``` option. All error types not specified
there will be forwarded to internal php error handler:

```php
...
    'components' => [
        'errorHandler' => [
            'class' => 'cronfy\yii\web\ErrorHandler',
            'typesToHandle' => E_ALL & ~E_NOTICE,

	        // NOTE: although E_ALL is set here, PHP Notices will not be converted to exceptions, 
	        // because they were disabled via 'typesToHandle' option above.
	        // PHP Warnings and other errors will be converted to exceptions.
            'typesToExceptions' => E_ALL,
        ],
    ],
...
```

