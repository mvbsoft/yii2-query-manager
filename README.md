Query Manager
=============
A query generator that allows visually generating a query to a MongoDB or PostgreSQL database using a frontend. Additionally, it provides the ability to use the generated queries for PHP arrays.

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mvbsoft/yii2-query-manager "*"
```

or add

```
"mvbsoft/yii2-query-manager": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \mvbsoft\queryManager\AutoloadExample::widget(); ?>```
