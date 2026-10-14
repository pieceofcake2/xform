# Xform (CakePHP Plugin)

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/xform?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/xform?label=Packagist)](https://packagist.org/packages/pieceofcake2/xform)
![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/xform/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?label=PHPStan)](https://github.com/pieceofcake2/xform/actions/workflows/CI.yml)
![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/xform/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/xform/CI.yml?label=CI)](https://github.com/pieceofcake2/xform/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/xform?label=Coverage)](https://codecov.io/gh/pieceofcake2/xform)

__This is forked for CakePHP2.__

Extends cakephp Form helper.

## Installation

```
composer require pieceofcake2/xform
```

## Config

* load plugin in bootstrap
  `CakePlugin::load('Xform');`
* Include the helper in your `controller.php`:
  `var $helpers = array('Form', 'Xform.Xform');`
* call method of XformHelper in your view.
  `echo $this->Xform->input('title');`

## Usage

On confirmation screen, this helper just show value of post data
  instead of making form tags.

On form input screen, this helper behaves same as form helper.

How does this helper know on confirmation screen?
When the confirmation transition, do following 1 or 2.

1. in controller
   `$this->params['xformHelperConfirmFlag'] = true;`
2. in controller or view file
   `$this->XformHelper->confirmScreenFlag = true;`

If you want to mask a password field on confirmation screen,
  use password method instead of input method.

If you want to change separator of datetime,
  set separator value on the changeDatetimeSeparator property.
