# Xform Documentation

pieceofcake2/xform provides `XformHelper`, an extension of CakePHP's `FormHelper` for
input → confirmation → complete form flows. On the input screen it behaves the same as
`FormHelper`. On the confirmation screen the same view code outputs the submitted values as
text instead of form controls.

- [Installation](#installation)
- [Usage](#usage)
- [Confirmation Screen](#confirmation-screen)
- [Options](#options)
- [XformjpHelper](#xformjphelper)

## Installation

Install the plugin with [Composer](https://getcomposer.org/):

```bash
composer require pieceofcake2/xform
```

The plugin has the `cakephp-plugin` type, so it is installed into your plugins directory
by `composer/installers`. Set `installer-paths` in your application's `composer.json` so it
is installed into the right place:

| Layout | `installer-paths` |
| --- | --- |
| Modern (`config/`, `src/`, `templates/`, `plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
| Legacy (`app/Config/`, `app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

Load the plugin in `config/bootstrap.php` (legacy layout: `app/Config/bootstrap.php`).
If you already use `CakePlugin::loadAll()`, this is not necessary:

```php
CakePlugin::load('Xform');
```

## Usage

Add the helper to your controller, e.g. `src/Controller/AppController.php` (legacy layout:
`app/Controller/AppController.php`):

```php
class AppController extends Controller
{
    public $helpers = ['Form', 'Xform.Xform'];
}
```

Use `$this->Xform` in your views like `$this->Form`:

```php
echo $this->Xform->create('Contact');
echo $this->Xform->input('name');
echo $this->Xform->password('password');
echo $this->Xform->textarea('message');
echo $this->Xform->end('Submit');
```

## Confirmation Screen

On the confirmation screen, `input()`, `text()`, `password()`, `textarea()`, `select()`,
`radio()`, `checkbox()`, `dateTime()` and the other input methods output the submitted value
(from `$this->request->data`) instead of a form control:

- Values are HTML-escaped and `nl2br()` is applied.
- For `select()` and `radio()`, the label of the selected option is shown.
- Multiple values (e.g. multiple checkboxes) are joined with `, `.
- `password()` shows `*****` instead of the value.

Tell the helper that the current screen is the confirmation screen in one of two ways:

1. In the controller, set the `xformHelperConfirmFlag` request parameter:

   ```php
   public function confirm()
   {
       $this->request->params['xformHelperConfirmFlag'] = true;
   }
   ```

2. In the controller or the view, set the helper's `confirmScreenFlag` property:

   ```php
   $this->Xform->confirmScreenFlag = true;
   ```

On the input screen, `password()` does not fill in the submitted password (see
`notFillinPasswordValue` below).

## Options

The helper's options are public properties. Set them in the helper settings:

```php
public $helpers = [
    'Form',
    'Xform.Xform' => [
        'confirmJoinSeparator' => ' / ',
        'inputDefaultOptions' => ['div' => false],
    ],
];
```

or in a view:

```php
$this->Xform->doNl2br = false;
```

| Property | Default | Description |
| --- | --- | --- |
| `confirmScreenFlag` | `false` | Whether the current screen is the confirmation screen. |
| `notFillinPasswordValue` | `true` | Do not fill in the submitted value of `password()` on the input screen. |
| `doHtmlEscape` | `true` | HTML-escape values on the confirmation screen. |
| `doNl2br` | `true` | Apply `nl2br()` to values on the confirmation screen. |
| `escapeBrPermanent` | `false` | If `false`, `doHtmlEscape` and `doNl2br` are reset to `true` after each value is output, so a change applies to the next value only. Set it to `true` to keep the changes. |
| `confirmJoinSeparator` | `', '` | Separator for multiple values on the confirmation screen. |
| `changeDatetimeSeparator` | `null` | Separators of `dateTime()` on the input and confirmation screens (see below). |
| `inputDefaultOptions` | `[]` | Default options of `input()`. |
| `monthNameSetNumber` | `false` | Show months as numbers instead of names in `dateTime()`. |

`changeDatetimeSeparator` sets the separators between the date and time parts:

```php
$this->Xform->changeDatetimeSeparator = [
    'datefmt' => [
        'year' => ' / ',
        'month' => ' / ',
        'day' => '',
        'afterDateTag' => '&nbsp;&nbsp;&nbsp;', // between the date and the time
    ],
    'timefmt' => [
        'hour' => ' : ',
        'min' => '',
        'meridian' => '',
    ],
];
```

## XformjpHelper

`Xform.Xformjp` is a variant of `XformHelper` for Japanese forms:

- `dateTime()` uses the separators `年`, `月`, `日`, `時` and `分`, and shows months as numbers.
- `input()` defaults to `['label' => false, 'error' => false, 'div' => false]`.
- `create()` and `end()` output only the `<form>` and `<input>` tags (the hidden fields are not
  wrapped in a `<div style="display:none;">`), for old Japanese mobile phones that could not
  send the hidden token fields.

```php
public $helpers = ['Form', 'Xform.Xformjp'];
```
