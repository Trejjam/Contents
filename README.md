Contents
=====


Installation
------------

The best way to install Trejjam/Utils is using  [Composer](http://getcomposer.org/):

```sh
$ composer require trejjam/contents
```

Configuration
-------------

.neon
```yml
extensions:
	contents: Trejjam\Contents\DI\UtilsExtension

contents:
	configurationDirectory: '%appDir%/config/contents'
	logDirectory          : NULL
	subTypes              : []
```