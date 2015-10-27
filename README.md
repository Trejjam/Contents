Contents
=====

[![Latest stable](https://img.shields.io/packagist/v/trejjam/contents.svg)](https://packagist.org/packages/trejjam/contents)

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
	contents: Trejjam\Contents\DI\ContentsExtension

contents:
	configurationDirectory: '%appDir%/config/contents'
	logDirectory          : NULL
	subTypes              : []
```
