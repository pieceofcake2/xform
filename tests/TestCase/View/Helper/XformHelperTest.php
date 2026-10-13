<?php
/**
 * XformHelper Test
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010, Yasushi Ichikawa http://github.com/ichikaway/
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('Controller', 'Controller');
App::uses('View', 'View');
App::uses('Model', 'Model');
App::uses('XformHelper', 'Xform.View/Helper');

/**
 * XformTestContact
 */
class XformTestContact extends Model
{
    /**
     * @var string
     */
    public $name = 'XformTestContact';

    /**
     * @var string
     */
    public $alias = 'Contact';

    /**
     * @var string|false
     */
    public $useTable = false;

    /**
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 11],
        'name' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 255],
        'password' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 255],
        'body' => ['type' => 'text', 'null' => true, 'default' => null, 'length' => null],
        'gender' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 1],
        'agree' => ['type' => 'boolean', 'null' => true, 'default' => null, 'length' => 1],
        'birthday' => ['type' => 'date', 'null' => true, 'default' => null, 'length' => null],
        'published' => ['type' => 'datetime', 'null' => true, 'default' => null, 'length' => null],
    ];
}

/**
 * XformHelperTest
 *
 * @property XformHelper $Xform
 */
class XformHelperTest extends CakeTestCase
{
    /**
     * @var View
     */
    public $View;

    /**
     * @var XformHelper
     */
    public $Xform;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.base', '');
        $request = new CakeRequest('contacts/add', false);
        $request->here = '/contacts/add';
        $request->webroot = '';
        $request->base = '';
        $request->addParams([
            'controller' => 'contacts',
            'action' => 'add',
            'models' => ['Contact' => ['plugin' => null, 'className' => 'XformTestContact']],
        ]);
        Router::setRequestInfo($request);

        $Controller = new Controller($request);
        $this->View = new View($Controller);
        ClassRegistry::addObject('Contact', new XformTestContact());

        $this->Xform = $this->_createHelper();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Xform, $this->View);
        ClassRegistry::flush();
        parent::tearDown();
    }

    /**
     * Create the helper under test.
     *
     * @param array $config Helper settings
     * @return XformHelper
     */
    protected function _createHelper(array $config = []): XformHelper
    {
        $helper = new XformHelper($this->View, $config);
        $helper->request = $this->View->request;

        return $helper;
    }

    /**
     * Put the helper into confirmation mode with the given request data.
     *
     * @param array $data Request data
     * @return void
     */
    protected function _confirm(array $data): void
    {
        $this->Xform->request->data = $data;
        $this->Xform->confirmScreenFlag = true;
    }

    public function testConstructorAppliesConfig(): void
    {
        $helper = $this->_createHelper([
            'confirmScreenFlag' => true,
            'confirmJoinSeparator' => ' / ',
            'doNl2br' => false,
        ]);

        $this->assertTrue($helper->confirmScreenFlag);
        $this->assertSame(' / ', $helper->confirmJoinSeparator);
        $this->assertFalse($helper->doNl2br);
    }

    public function testCheckConfirmScreen(): void
    {
        $this->assertFalse($this->Xform->checkConfirmScreen());

        $this->Xform->confirmScreenFlag = true;
        $this->assertTrue($this->Xform->checkConfirmScreen());

        $this->Xform->confirmScreenFlag = false;
        $this->Xform->request->params['xformHelperConfirmFlag'] = true;
        $this->assertTrue($this->Xform->checkConfirmScreen());

        $this->Xform->request->params['xformHelperConfirmFlag'] = 1;
        $this->assertFalse($this->Xform->checkConfirmScreen(), 'Only a strict true enables the confirmation screen');
    }

    public function testTextOnInputScreen(): void
    {
        $this->Xform->request->data = ['Contact' => ['name' => 'Taro']];

        $result = $this->Xform->text('Contact.name');
        $this->assertTags($result, [
            'input' => ['name' => 'data[Contact][name]', 'value' => 'Taro', 'type' => 'text', 'id' => 'ContactName'],
        ]);
    }

    public function testTextOnConfirmScreen(): void
    {
        $this->_confirm(['Contact' => ['name' => "<b>Taro</b>\nYamada"]]);

        $result = $this->Xform->text('Contact.name');
        $this->assertSame("&lt;b&gt;Taro&lt;/b&gt;<br />\nYamada", $result);
    }

    public function testConfirmScreenByRequestParam(): void
    {
        $this->Xform->request->data = ['Contact' => ['name' => 'Taro']];
        $this->Xform->request->params['xformHelperConfirmFlag'] = true;

        $this->assertSame('Taro', $this->Xform->text('Contact.name'));
    }

    public function testConfirmInputWithoutModelPrefix(): void
    {
        $this->_confirm(['Contact' => ['name' => 'Taro']]);

        $this->assertSame('Taro', $this->Xform->text('name'));
    }

    public function testConfirmInputFallsBackToFirstModelData(): void
    {
        unset($this->Xform->request->params['models']);
        $this->_confirm(['Other' => ['name' => 'Hanako']]);

        $this->assertSame('Hanako', $this->Xform->getConfirmInput('name'));
    }

    public function testConfirmInputWithoutDataForCurrentModel(): void
    {
        $this->_confirm(['Other' => ['name' => 'Hanako']]);

        $this->assertSame('', $this->Xform->getConfirmInput('name'));
    }

    public function testConfirmInputForIndexedField(): void
    {
        $this->_confirm([
            'Contact' => [
                0 => ['name' => 'First'],
                1 => ['name' => 'Second'],
            ],
        ]);

        $this->assertSame('First', $this->Xform->getConfirmInput('Contact.0.name'));
        $this->assertSame('Second', $this->Xform->getConfirmInput('Contact.1.name'));
    }

    public function testConfirmInputForMissingField(): void
    {
        $this->_confirm(['Contact' => ['name' => 'Taro']]);

        $this->assertSame('', $this->Xform->getConfirmInput('Contact.body'));
    }

    public function testPasswordOnInputScreenClearsValue(): void
    {
        $this->Xform->request->data = ['Contact' => ['password' => 'secret']];

        $result = $this->Xform->password('Contact.password');
        $this->assertTags($result, [
            'input' => ['name' => 'data[Contact][password]', 'value' => '', 'type' => 'password', 'id' => 'ContactPassword'],
        ]);
    }

    public function testPasswordOnInputScreenKeepsValue(): void
    {
        $this->Xform->notFillinPasswordValue = false;
        $this->Xform->request->data = ['Contact' => ['password' => 'secret']];

        $result = $this->Xform->password('Contact.password');
        $this->assertTags($result, [
            'input' => ['name' => 'data[Contact][password]', 'value' => 'secret', 'type' => 'password', 'id' => 'ContactPassword'],
        ]);
    }

    public function testPasswordOnConfirmScreenIsMasked(): void
    {
        $this->_confirm(['Contact' => ['password' => 'secret']]);
        $this->assertSame('*****', $this->Xform->password('Contact.password'));

        $this->_confirm(['Contact' => ['password' => '']]);
        $this->assertSame('', $this->Xform->password('Contact.password'));
    }

    public function testTextareaOnInputScreen(): void
    {
        $this->Xform->request->data = ['Contact' => ['body' => 'Hello']];

        $result = $this->Xform->textarea('Contact.body');
        $this->assertTags($result, [
            'textarea' => ['name' => 'data[Contact][body]', 'id' => 'ContactBody'],
            'Hello',
            '/textarea',
        ]);
    }

    public function testTextareaOnConfirmScreen(): void
    {
        $this->_confirm(['Contact' => ['body' => "Line1\nLine2 & <i>3</i>"]]);

        $result = $this->Xform->textarea('Contact.body');
        $this->assertSame("Line1<br />\nLine2 &amp; &lt;i&gt;3&lt;/i&gt;", $result);
    }

    public function testEscapeAndNl2brAreResetAfterOutput(): void
    {
        $this->_confirm(['Contact' => ['body' => "<b>A</b>\nB"]]);

        $this->Xform->doHtmlEscape = false;
        $this->Xform->doNl2br = false;
        $this->assertSame("<b>A</b>\nB", $this->Xform->textarea('Contact.body'));
        $this->assertTrue($this->Xform->doHtmlEscape);
        $this->assertTrue($this->Xform->doNl2br);

        $this->assertSame("&lt;b&gt;A&lt;/b&gt;<br />\nB", $this->Xform->textarea('Contact.body'));
    }

    public function testEscapeAndNl2brArePermanent(): void
    {
        $this->_confirm(['Contact' => ['body' => "<b>A</b>\nB"]]);

        $this->Xform->escapeBrPermanent = true;
        $this->Xform->doHtmlEscape = false;
        $this->Xform->doNl2br = false;
        $this->assertSame("<b>A</b>\nB", $this->Xform->textarea('Contact.body'));
        $this->assertSame("<b>A</b>\nB", $this->Xform->textarea('Contact.body'));
        $this->assertFalse($this->Xform->doHtmlEscape);
        $this->assertFalse($this->Xform->doNl2br);
    }

    public function testSelectOnInputScreen(): void
    {
        $this->Xform->request->data = ['Contact' => ['gender' => 'f']];

        $result = $this->Xform->select('Contact.gender', ['m' => 'Male', 'f' => 'Female'], ['empty' => false]);
        $this->assertTags($result, [
            'select' => ['name' => 'data[Contact][gender]', 'id' => 'ContactGender'],
            ['option' => ['value' => 'm']],
            'Male',
            '/option',
            ['option' => ['value' => 'f', 'selected' => 'selected']],
            'Female',
            '/option',
            '/select',
        ]);
    }

    public function testSelectOnConfirmScreenShowsLabel(): void
    {
        $this->_confirm(['Contact' => ['gender' => 'f']]);

        $this->assertSame('Female', $this->Xform->select('Contact.gender', ['m' => 'Male', 'f' => 'Female']));
    }

    public function testSelectOnConfirmScreenWithUnknownValue(): void
    {
        $this->_confirm(['Contact' => ['gender' => 'x']]);

        $this->assertSame('x', $this->Xform->select('Contact.gender', ['m' => 'Male', 'f' => 'Female']));
    }

    public function testMultipleSelectOnConfirmScreenJoinsLabels(): void
    {
        $this->_confirm(['Contact' => ['gender' => ['m', 'f']]]);

        $options = ['m' => 'Male', 'f' => 'Female'];
        $this->assertSame('Male, Female', $this->Xform->select('Contact.gender', $options, ['multiple' => 'checkbox']));

        $this->Xform->confirmJoinSeparator = ' & ';
        $this->assertSame('Male &amp; Female', $this->Xform->select('Contact.gender', $options, ['multiple' => 'checkbox']));
    }

    public function testRadioOnInputScreen(): void
    {
        $this->Xform->request->data = ['Contact' => ['gender' => 'm']];

        $result = $this->Xform->radio('Contact.gender', ['m' => 'Male'], ['legend' => false, 'hiddenField' => false]);
        $this->assertTags($result, [
            'input' => ['type' => 'radio', 'name' => 'data[Contact][gender]', 'id' => 'ContactGenderM', 'value' => 'm', 'checked' => 'checked'],
            'label' => ['for' => 'ContactGenderM'],
            'Male',
            '/label',
        ]);
    }

    public function testRadioOnConfirmScreenShowsLabel(): void
    {
        $this->_confirm(['Contact' => ['gender' => 'm']]);

        $this->assertSame('Male', $this->Xform->radio('Contact.gender', ['m' => 'Male', 'f' => 'Female']));
    }

    public function testCheckboxOnInputScreen(): void
    {
        $this->Xform->request->data = ['Contact' => ['agree' => '1']];

        $result = $this->Xform->checkbox('Contact.agree', ['hiddenField' => false]);
        $this->assertTags($result, [
            'input' => ['type' => 'checkbox', 'name' => 'data[Contact][agree]', 'value' => '1', 'id' => 'ContactAgree', 'checked' => 'checked'],
        ]);
    }

    public function testCheckboxOnConfirmScreen(): void
    {
        $this->_confirm(['Contact' => ['agree' => '1']]);

        $this->assertSame('1', $this->Xform->checkbox('Contact.agree'));
    }

    public function testInputMergesDefaultOptions(): void
    {
        $this->Xform->inputDefaultOptions = ['div' => false, 'label' => false];

        $result = $this->Xform->input('Contact.name');
        $this->assertTags($result, [
            'input' => ['name' => 'data[Contact][name]', 'type' => 'text', 'maxlength' => '255', 'id' => 'ContactName'],
        ]);

        $result = $this->Xform->input('Contact.name', ['label' => 'Name']);
        $this->assertTags($result, [
            'label' => ['for' => 'ContactName'],
            'Name',
            '/label',
            'input' => ['name' => 'data[Contact][name]', 'type' => 'text', 'maxlength' => '255', 'id' => 'ContactName'],
        ]);
    }

    public function testInputOnConfirmScreen(): void
    {
        $this->_confirm(['Contact' => ['name' => 'Taro']]);

        $result = $this->Xform->input('Contact.name');
        $this->assertTags($result, [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'ContactName'],
            'Name',
            '/label',
            'Taro',
            '/div',
        ]);
    }

    public function testErrorIsWrappedByDefault(): void
    {
        $Contact = ClassRegistry::getObject('Contact');
        $Contact->validationErrors['name'] = ['Required'];

        $result = $this->Xform->error('Contact.name');
        $this->assertTags($result, [
            'div' => ['class' => 'error-message'],
            'Required',
            '/div',
        ]);

        $this->assertSame('Required', $this->Xform->error('Contact.name', null, ['wrap' => false]));
    }

    public function testDateTimeOnInputScreen(): void
    {
        $this->Xform->request->data = ['Contact' => ['birthday' => '2010-04-05 00:00:00']];

        $result = $this->Xform->dateTime('Contact.birthday', 'YMD', 'NONE', ['empty' => false, 'minYear' => 2009, 'maxYear' => 2011]);
        $this->assertSame(3, substr_count($result, '<select'));
        $this->assertMatchesRegularExpression('#</select>- <select name="data\[Contact\]\[birthday\]\[month\]"#', $result);
        $this->assertMatchesRegularExpression('#</select>- <select name="data\[Contact\]\[birthday\]\[day\]"#', $result);
        $this->assertStringContainsString('<option value="04" selected="selected">April</option>', $result);
        $this->assertStringNotContainsString('__/__', $result);
    }

    public function testDateTimeOnInputScreenWithSeparatorsAndNumericMonths(): void
    {
        $this->Xform->monthNameSetNumber = true;
        $this->Xform->changeDatetimeSeparator = [
            'datefmt' => ['year' => ' / ', 'month' => ' / ', 'day' => '', 'afterDateTag' => ' @ '],
            'timefmt' => ['hour' => ' : ', 'min' => '', 'meridian' => ''],
        ];
        $this->Xform->request->data = ['Contact' => ['published' => '2010-04-05 13:07:00']];

        $result = $this->Xform->dateTime('Contact.published', 'YMD', '24', ['empty' => false, 'minYear' => 2009, 'maxYear' => 2011]);
        $this->assertSame(5, substr_count($result, '<select'));
        $this->assertMatchesRegularExpression('#</select> /  <select name="data\[Contact\]\[published\]\[month\]"#', $result);
        $this->assertMatchesRegularExpression('#</select> @ <select name="data\[Contact\]\[published\]\[hour\]"#', $result);
        $this->assertMatchesRegularExpression('#</select> :  <select name="data\[Contact\]\[published\]\[min\]"#', $result);
        $this->assertStringContainsString('<option value="04" selected="selected">04</option>', $result);
    }

    public function testDateTimeOnInputScreenWith12HourClock(): void
    {
        $this->Xform->request->data = ['Contact' => ['published' => '2010-04-05 13:07:00']];

        $result = $this->Xform->dateTime('Contact.published', 'NONE', '12', ['empty' => false]);
        $this->assertSame(3, substr_count($result, '<select'));
        $this->assertMatchesRegularExpression('#</select>: <select name="data\[Contact\]\[published\]\[min\]"#', $result);
        $this->assertMatchesRegularExpression('#</select><select name="data\[Contact\]\[published\]\[meridian\]"#', $result);
        $this->assertStringContainsString('<option value="pm" selected="selected">pm</option>', $result);
    }

    public function testDateTimeOnConfirmScreen(): void
    {
        $this->_confirm([
            'Contact' => [
                'published' => ['year' => '2010', 'month' => '04', 'day' => '05', 'hour' => '9', 'min' => '7'],
            ],
        ]);

        $this->assertSame('2010-04-05', $this->Xform->dateTime('Contact.published', 'YMD', 'NONE'));
        $this->assertSame('2010-04-05 09:07', $this->Xform->dateTime('Contact.published', 'YMD', '24'));
        $this->assertSame('2010/04/05 09:07', $this->Xform->dateTime('Contact.published', 'YMD', '24', ['separator' => '/']));
    }

    public function testDateTimeOnConfirmScreenWithCustomSeparators(): void
    {
        $this->Xform->changeDatetimeSeparator = [
            'datefmt' => ['year' => '.', 'month' => '.', 'day' => '', 'afterDateTag' => ''],
            'timefmt' => ['hour' => 'h', 'min' => 'm', 'meridian' => ''],
        ];
        $this->_confirm([
            'Contact' => [
                'published' => ['year' => '2010', 'month' => '04', 'day' => '05', 'hour' => '9', 'min' => '7', 'meridian' => 'am'],
            ],
        ]);

        $this->assertSame('2010.04.05 09h07mam', $this->Xform->dateTime('Contact.published', 'YMD', '12'));
    }

    public function testDateTimeOnConfirmScreenWithEmptyOrScalarValue(): void
    {
        $this->_confirm(['Contact' => ['published' => ['year' => '', 'month' => '', 'day' => '']]]);
        $this->assertSame('', $this->Xform->dateTime('Contact.published', 'YMD', 'NONE'));

        $this->_confirm(['Contact' => ['published' => '2010-04-05 09:07:00']]);
        $this->assertSame('2010-04-05 09:07:00', $this->Xform->dateTime('Contact.published'));

        $this->_confirm(['Contact' => ['name' => 'Taro']]);
        $this->assertSame('', $this->Xform->getConfirmDatetime('Contact.published'));
    }
}
