<?php
/**
 * XformjpHelper Test
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
App::uses('XformjpHelper', 'Xform.View/Helper');

/**
 * XformjpTestContact
 */
class XformjpTestContact extends Model
{
    /**
     * @var string
     */
    public $name = 'XformjpTestContact';

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
        'published' => ['type' => 'datetime', 'null' => true, 'default' => null, 'length' => null],
    ];
}

/**
 * XformjpHelperTest
 */
class XformjpHelperTest extends CakeTestCase
{
    /**
     * @var XformjpHelper
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
        Configure::write('Security.salt', 'foo!');
        $request = new CakeRequest('contacts/add', false);
        $request->here = '/contacts/add';
        $request->webroot = '';
        $request->base = '';
        $request->addParams([
            'controller' => 'contacts',
            'action' => 'add',
            'models' => ['Contact' => ['plugin' => null, 'className' => 'XformjpTestContact']],
        ]);
        Router::setRequestInfo($request);

        $View = new View(new Controller($request));
        ClassRegistry::addObject('Contact', new XformjpTestContact());

        $this->Xform = new XformjpHelper($View);
        $this->Xform->request = $request;
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Xform);
        ClassRegistry::flush();
        parent::tearDown();
    }

    public function testDefaults(): void
    {
        $this->assertInstanceOf(XformHelper::class, $this->Xform);
        $this->assertTrue($this->Xform->monthNameSetNumber);
        $this->assertSame(['label' => false, 'error' => false, 'div' => false], $this->Xform->inputDefaultOptions);
        $this->assertSame('年', $this->Xform->changeDatetimeSeparator['datefmt']['year']);
        $this->assertSame('時', $this->Xform->changeDatetimeSeparator['timefmt']['hour']);
    }

    public function testInputRendersOnlyTheControl(): void
    {
        $result = $this->Xform->input('Contact.name');
        $this->assertTags($result, [
            'input' => ['name' => 'data[Contact][name]', 'type' => 'text', 'maxlength' => '255', 'id' => 'ContactName'],
        ]);
    }

    public function testCreateKeepsOnlyFormAndInputTags(): void
    {
        $result = $this->Xform->create('Contact', ['url' => '/contacts/add']);
        $this->assertTags($result, [
            'form' => ['action' => '/contacts/add', 'id' => 'ContactAddForm', 'method' => 'post', 'accept-charset' => 'utf-8'],
            'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
        ]);
        $this->assertStringNotContainsString('<div', $result);
    }

    public function testEndKeepsOnlyFormAndInputTags(): void
    {
        $this->Xform->create('Contact', ['url' => '/contacts/add']);

        $result = $this->Xform->end('Send');
        $this->assertTags($result, [
            'input' => ['type' => 'submit', 'value' => 'Send'],
            '/form',
        ]);
        $this->assertStringNotContainsString('<div', $result);
    }

    public function testEndWithSecurityTokenHasNoHiddenDiv(): void
    {
        $this->Xform->request->params['_Token'] = ['key' => 'testKey', 'unlockedFields' => []];
        $this->Xform->create('Contact', ['url' => '/contacts/add']);

        $result = $this->Xform->end();
        $this->assertStringNotContainsString('<div', $result);
        $this->assertStringNotContainsString('display:none', $result);
        $this->assertStringContainsString('name="data[_Token][fields]"', $result);
        $this->assertStringEndsWith('</form>', $result);
    }

    public function testDateTimeOnInputScreenUsesJapaneseSeparators(): void
    {
        $this->Xform->request->data = ['Contact' => ['published' => '2010-04-05 13:07:00']];

        $result = $this->Xform->dateTime('Contact.published', 'YMD', '24', ['empty' => false, 'minYear' => 2009, 'maxYear' => 2011]);
        $this->assertMatchesRegularExpression('#</select>年 <select name="data\[Contact\]\[published\]\[month\]"#', $result);
        $this->assertMatchesRegularExpression('#</select>月 <select name="data\[Contact\]\[published\]\[day\]"#', $result);
        $this->assertMatchesRegularExpression('#</select>日&nbsp;&nbsp;&nbsp;<select name="data\[Contact\]\[published\]\[hour\]"#', $result);
        $this->assertMatchesRegularExpression('#</select>時 <select name="data\[Contact\]\[published\]\[min\]"#', $result);
        $this->assertStringEndsWith('</select>分', $result);
        $this->assertStringContainsString('<option value="04" selected="selected">04</option>', $result);
    }

    public function testDateTimeOnConfirmScreenUsesJapaneseSeparators(): void
    {
        $this->Xform->confirmScreenFlag = true;
        $this->Xform->request->data = [
            'Contact' => [
                'published' => ['year' => '2010', 'month' => '04', 'day' => '05', 'hour' => '9', 'min' => '7'],
            ],
        ];

        $this->assertSame('2010年04月05日', $this->Xform->dateTime('Contact.published', 'YMD', 'NONE'));
        $this->assertSame('2010年04月05日 09時07分', $this->Xform->dateTime('Contact.published', 'YMD', '24'));
    }
}
