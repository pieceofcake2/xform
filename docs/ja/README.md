# Xform ドキュメント

pieceofcake2/xform は、入力 → 確認 → 完了という流れのフォームのために、CakePHP の `FormHelper` を
拡張した `XformHelper` を提供します。入力画面では `FormHelper` と同じように動作します。確認画面では、
同じビューのコードがフォームコントロールの代わりに送信された値をテキストとして出力します。

- [インストール](#installation)
- [使い方](#usage)
- [確認画面](#confirmation-screen)
- [オプション](#options)
- [XformjpHelper](#xformjphelper)

<a id="installation"></a>

## インストール

[Composer](https://getcomposer.org/) でプラグインをインストールします。

```bash
composer require pieceofcake2/xform
```

プラグインのタイプは `cakephp-plugin` なので、`composer/installers` によってプラグインディレクトリに
インストールされます。正しい場所にインストールされるように、アプリケーションの `composer.json` で
`installer-paths` を設定してください。

| レイアウト | `installer-paths` |
| --- | --- |
| モダン (`config/`, `src/`, `templates/`, `plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
| レガシー (`app/Config/`, `app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

`config/bootstrap.php` (レガシーレイアウトでは `app/Config/bootstrap.php`) でプラグインを読み込みます。
すでに `CakePlugin::loadAll()` を使っている場合は不要です。

```php
CakePlugin::load('Xform');
```

<a id="usage"></a>

## 使い方

コントローラー (例: `src/Controller/AppController.php`、レガシーレイアウトでは
`app/Controller/AppController.php`) にヘルパーを追加します。

```php
class AppController extends Controller
{
    public $helpers = ['Form', 'Xform.Xform'];
}
```

ビューでは `$this->Form` と同じように `$this->Xform` を使います。

```php
echo $this->Xform->create('Contact');
echo $this->Xform->input('name');
echo $this->Xform->password('password');
echo $this->Xform->textarea('message');
echo $this->Xform->end('Submit');
```

<a id="confirmation-screen"></a>

## 確認画面

確認画面では、`input()`、`text()`、`password()`、`textarea()`、`select()`、`radio()`、
`checkbox()`、`dateTime()` などの入力用メソッドが、フォームコントロールの代わりに
(`$this->request->data` から) 送信された値を出力します。

- 値は HTML エスケープされ、`nl2br()` が適用されます。
- `select()` と `radio()` では、選択された選択肢のラベルが表示されます。
- 複数の値 (例: 複数のチェックボックス) は `, ` で連結されます。
- `password()` は値の代わりに `*****` を表示します。

現在の画面が確認画面であることは、次の 2 つのどちらかの方法でヘルパーに伝えます。

1. コントローラーで、リクエストパラメーター `xformHelperConfirmFlag` を設定します。

   ```php
   public function confirm()
   {
       $this->request->params['xformHelperConfirmFlag'] = true;
   }
   ```

2. コントローラーかビューで、ヘルパーの `confirmScreenFlag` プロパティを設定します。

   ```php
   $this->Xform->confirmScreenFlag = true;
   ```

入力画面では、`password()` は送信されたパスワードを入力欄に埋めません (後述の
`notFillinPasswordValue` を参照してください)。

<a id="options"></a>

## オプション

ヘルパーのオプションは public プロパティです。ヘルパーの設定で指定するか、

```php
public $helpers = [
    'Form',
    'Xform.Xform' => [
        'confirmJoinSeparator' => ' / ',
        'inputDefaultOptions' => ['div' => false],
    ],
];
```

ビューで設定します。

```php
$this->Xform->doNl2br = false;
```

| プロパティ | デフォルト | 説明 |
| --- | --- | --- |
| `confirmScreenFlag` | `false` | 現在の画面が確認画面かどうか。 |
| `notFillinPasswordValue` | `true` | 入力画面で `password()` に送信された値を埋めない。 |
| `doHtmlEscape` | `true` | 確認画面で値を HTML エスケープする。 |
| `doNl2br` | `true` | 確認画面で値に `nl2br()` を適用する。 |
| `escapeBrPermanent` | `false` | `false` の場合、値を 1 つ出力するたびに `doHtmlEscape` と `doNl2br` が `true` に戻るので、変更は次の値にだけ適用されます。変更を維持するには `true` にします。 |
| `confirmJoinSeparator` | `', '` | 確認画面で複数の値を連結する区切り文字。 |
| `changeDatetimeSeparator` | `null` | 入力画面と確認画面での `dateTime()` の区切り文字 (後述)。 |
| `inputDefaultOptions` | `[]` | `input()` のデフォルトのオプション。 |
| `monthNameSetNumber` | `false` | `dateTime()` で月を名前ではなく数字で表示する。 |

`changeDatetimeSeparator` は、日付と時刻の各部分の間の区切り文字を設定します。

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

<a id="xformjphelper"></a>

## XformjpHelper

`Xform.Xformjp` は、日本語のフォーム向けの `XformHelper` の派生版です。

- `dateTime()` は区切り文字に `年`、`月`、`日`、`時`、`分` を使い、月を数字で表示します。
- `input()` のデフォルトは `['label' => false, 'error' => false, 'div' => false]` です。
- `create()` と `end()` は `<form>` と `<input>` タグだけを出力します (hidden フィールドを
  `<div style="display:none;">` で囲みません)。これは、hidden のトークンフィールドを送信できなかった
  昔の日本の携帯電話のためです。

```php
public $helpers = ['Form', 'Xform.Xformjp'];
```

<a id="origin"></a>

## 由来

このパッケージは
[ichikaway/xformHelper](https://github.com/ichikaway/xformHelper) (Packagist では `ichikaway/xform`、
アップストリームの最終バージョンは 2.0.0) のメンテナンスされたフォークで、元の作者は Yasushi Ichikawa です。
[pieceofcake2](https://github.com/pieceofcake2) が、モダンな PHP で動く CakePHP 2 向けに
メンテナンスしています。オリジナルからの変更点は
[フォーク以降のコミット履歴](https://github.com/pieceofcake2/xform/compare/ab13a90...2.x)
で確認できます。
