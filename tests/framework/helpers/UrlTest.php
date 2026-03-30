<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\base\Action;
use yii\base\InvalidParamException;
use yii\base\Module;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\Controller;
use yii\web\Request;
use yii\web\UrlManager;
use yii\widgets\Menu;
use yiiunit\framework\filters\stubs\UserIdentity;
use yiiunit\TestCase;

/**
 * Unit tests for {@see Url} helper methods.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 2.0
 */
#[Group('helpers')]
class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication(
            [
                'components' => [
                    'request' => [
                        'class' => Request::class,
                        'cookieValidationKey' => '123',
                        'scriptUrl' => '/base/index.php',
                        'hostInfo' => 'http://example.com/',
                        'url' => '/base/index.php&r=site%2Fcurrent&id=42',
                    ],
                    'urlManager' => [
                        'class' => 'yii\web\UrlManager',
                        'baseUrl' => '/base',
                        'scriptUrl' => '/base/index.php',
                        'hostInfo' => 'http://example.com/',
                    ],
                    'user' => [
                        'identityClass' => UserIdentity::class,
                    ],
                ],
            ],
            Application::class
        );
    }

    protected function tearDown(): void
    {
        $session = Yii::$app->getSession();

        if ($session->getIsActive()) {
            $session->removeAll();
        }

        parent::tearDown();
    }

    /**
     * Mocks controller action with parameters.
     *
     * @phpstan-param array<string, mixed> $params Action parameters to be set in controller.
     */
    protected function mockAction(
        string $controllerId,
        string $actionID,
        string|null $moduleID = null,
        array $params = [],
    ): void {
        Yii::$app->controller = $controller = new Controller($controllerId, Yii::$app);

        $controller->actionParams = $params;
        $controller->action = new Action($actionID, $controller);

        if ($moduleID !== null) {
            $controller->module = new Module($moduleID);
        }
    }

    protected function removeMockedAction(): void
    {
        Yii::$app->controller = null;
    }

    public function testToRoute(): void
    {
        $this->mockAction('page', 'view', null, ['id' => 10]);

        self::assertSame(
            '/base/index.php?r=page%2Fview',
            Url::toRoute(''),
            "Empty 'route' should resolve to current 'route'.",
        );
        self::assertSame(
            '/base/index.php?r=',
            Url::toRoute('/'),
            "Slash 'route' should resolve to default 'route'.",
        );
        self::assertSame(
            'http://example.com/base/index.php?r=page%2Fview',
            Url::toRoute('', true),
            "Empty 'route' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php?r=page%2Fview',
            Url::toRoute('', 'https'),
            "Empty 'route' with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '//example.com/base/index.php?r=page%2Fview',
            Url::toRoute('', ''),
            "Empty 'route' with empty 'scheme' should produce 'protocol-relative' URL.",
        );

        self::assertSame(
            '/base/index.php?r=page%2Fedit',
            Url::toRoute('edit'),
            "Action-only 'route' should prepend controller 'uniqueId'.",
        );
        self::assertSame(
            '/base/index.php?r=page%2Fedit&id=20',
            Url::toRoute(['edit', 'id' => 20]),
            "Action 'route' with 'params' should include 'query' parameters.",
        );
        self::assertSame(
            'http://example.com/base/index.php?r=page%2Fedit&id=20',
            Url::toRoute(['edit', 'id' => 20], true),
            "Action 'route' with 'params' and absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php?r=page%2Fedit&id=20',
            Url::toRoute(['edit', 'id' => 20], 'https'),
            "Action 'route' with 'params' and 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '//example.com/base/index.php?r=page%2Fedit&id=20',
            Url::toRoute(['edit', 'id' => 20], ''),
            "Action 'route' with 'params' and empty 'scheme' should produce 'protocol-relative' URL.",
        );

        $this->mockAction('default', 'index', 'stats');

        self::assertSame(
            '/base/index.php?r=stats%2Fuser%2Fview',
            Url::toRoute('user/view'),
            "Module-relative 'route' should prepend module 'uniqueId'.",
        );
        self::assertSame(
            '/base/index.php?r=stats%2Fuser%2Fview&id=42',
            Url::toRoute(['user/view', 'id' => 42]),
            "Module-relative 'route' with 'params' should include 'query' parameters.",
        );
        self::assertSame(
            'http://example.com/base/index.php?r=stats%2Fuser%2Fview&id=42',
            Url::toRoute(['user/view', 'id' => 42], true),
            "Module-relative 'route' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php?r=stats%2Fuser%2Fview&id=42',
            Url::toRoute(['user/view', 'id' => 42], 'https'),
            "Module-relative 'route' with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '//example.com/base/index.php?r=stats%2Fuser%2Fview&id=42',
            Url::toRoute(['user/view', 'id' => 42], ''),
            "Module-relative 'route' with empty 'scheme' should produce 'protocol-relative' URL.",
        );

        Yii::setAlias('@userView', 'user/view');

        self::assertSame(
            '/base/index.php?r=stats%2Fuser%2Fview',
            Url::toRoute('@userView'),
            "Alias 'route' should resolve to the aliased 'route'.",
        );

        Yii::setAlias('@userView', null);

        $this->removeMockedAction();

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage(
            'Unable to resolve the relative route: site/view. No active controller is available.',
        );

        Url::toRoute('site/view');
    }

    public function testCurrent(): void
    {
        $this->mockAction('page', 'view', null, []);

        Yii::$app->request->setQueryParams(['id' => 10, 'name' => 'test', 10 => 0]);

        $uri = '/base/index.php?r=page%2Fview';

        self::assertSame(
            $uri . '&id=10&name=test&10=0',
            Url::current(),
            "Should return current URL with all 'GET' params.",
        );
        self::assertSame(
            $uri . '&id=20&name=test&10=0',
            Url::current(['id' => 20]),
            "Should override existing 'param' value.",
        );
        self::assertSame(
            $uri . '&name=test&10=0',
            Url::current(['id' => null]),
            "Should remove 'param' when value is 'null'.",
        );
        self::assertSame(
            $uri . '&name=test&10=0&1=yes',
            Url::current(['id' => [], 1 => 'yes']),
            "Should handle empty array 'param' and add new 'param'.",
        );
        self::assertSame(
            $uri . '&name=test&10=0',
            Url::current(['id' => []]),
            "Should handle empty array 'param' removal.",
        );
        self::assertSame(
            $uri . '&name=test',
            Url::current(['id' => null, 10 => null]),
            "Should remove multiple 'params' via 'null'.",
        );
        self::assertSame(
            $uri . '&name=test&1=yes',
            Url::current(['id' => null, 10 => null, 1 => 'yes']),
            "Should remove and add 'params' simultaneously.",
        );

        $params = ['arr' => ['attr_one' => 1, 'attr_two' => 2]];

        Yii::$app->request->setQueryParams($params);

        self::assertSame(
            $uri . '&arr%5Battr_one%5D=1&arr%5Battr_two%5D=2',
            Url::current(),
            "Should encode nested array 'params'.",
        );
        self::assertSame(
            $uri,
            Url::current(['arr' => null]),
            "Should remove entire nested array 'param' via 'null'.",
        );
        self::assertSame(
            $uri . '&arr%5Battr_two%5D=2',
            Url::current(['arr' => ['attr_one' => null]]),
            "Should remove single nested array 'key' via 'null'.",
        );
        self::assertSame(
            $uri . '&arr%5Battr_one%5D=1&arr%5Battr_two%5D=two',
            Url::current(['arr' => ['attr_two' => 'two']]),
            "Should override single nested array 'value'.",
        );
    }

    public function testPrevious(): void
    {
        Yii::$app->getUser()->login(UserIdentity::findIdentity('user1'));

        self::assertNull(
            Url::previous('notExistedPage'),
            "Should return 'null' for non-existent named URL.",
        );
        self::assertNull(
            Url::previous(Yii::$app->getUser()->returnUrlParam),
            "Should return 'null' for unset return URL 'param'.",
        );
        self::assertSame(
            '/base/index.php',
            Url::previous(),
            "Should return default 'returnUrl' when no name is specified.",
        );
    }

    public function testTo(): void
    {
        $this->mockAction('page', 'view', null, ['id' => 10]);

        self::assertSame(
            '/base/index.php?r=page%2Fedit&id=20',
            Url::to(['edit', 'id' => 20]),
            "Array 'route' should create URL with 'route' and 'params'.",
        );
        self::assertSame(
            '/base/index.php?r=page%2Fedit',
            Url::to(['edit']),
            "Array 'route' without 'params' should create URL with 'route' only.",
        );
        self::assertSame(
            '/base/index.php?r=page%2Fview',
            Url::to(['']),
            "Empty array 'route' should resolve to current 'route'.",
        );

        Yii::setAlias('@pageEdit', 'edit');

        self::assertSame(
            '/base/index.php?r=page%2Fedit&id=20',
            Url::to(['@pageEdit', 'id' => 20]),
            "Alias in array 'route' should resolve to aliased 'route'.",
        );

        Yii::setAlias('@pageEdit', null);

        self::assertSame(
            'http://example.com/base/index.php?r=page%2Fedit&id=20',
            Url::to(['edit', 'id' => 20], true),
            "Array 'route' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'http://example.com/base/index.php?r=page%2Fedit',
            Url::to(['edit'], true),
            "Array 'route' without 'params' and absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'http://example.com/base/index.php?r=page%2Fview',
            Url::to([''], true),
            "Empty array 'route' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php?r=page%2Fedit&id=20',
            Url::to(['edit', 'id' => 20], 'https'),
            "Array 'route' with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            'https://example.com/base/index.php?r=page%2Fedit',
            Url::to(['edit'], 'https'),
            "Array 'route' without 'params' and 'https' scheme should use 'https'.",
        );
        self::assertSame(
            'https://example.com/base/index.php?r=page%2Fview',
            Url::to([''], 'https'),
            "Empty array 'route' with 'https' scheme should use 'https'.",
        );

        $this->mockAction('page', 'view', null, ['id' => 10]);

        self::assertSame(
            '/base/index.php&r=site%2Fcurrent&id=42',
            Url::to(''),
            "Empty string should return currently requested 'URL'.",
        );
        self::assertSame(
            'http://example.com/base/index.php&r=site%2Fcurrent&id=42',
            Url::to('', true),
            "Empty string with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php&r=site%2Fcurrent&id=42',
            Url::to('', 'https'),
            "Empty string with 'https' scheme should use 'https'.",
        );

        Yii::setAlias('@web1', 'http://test.example.com/test/me1');
        Yii::setAlias('@web2', 'test/me2');
        Yii::setAlias('@web3', '');
        Yii::setAlias('@web4', '/test');
        Yii::setAlias('@web5', '#test');

        self::assertSame(
            'test/me1',
            Url::to('test/me1'),
            "Relative string 'URL' should be returned as-is.",
        );
        self::assertSame(
            'javascript:test/me1',
            Url::to('javascript:test/me1'),
            "JavaScript 'protocol' URL should be returned as-is.",
        );
        self::assertSame(
            'java/script:test/me1',
            Url::to('java/script:test/me1'),
            "Non-protocol colon 'URL' should be returned as-is.",
        );
        self::assertSame(
            '#test/me1',
            Url::to('#test/me1'),
            "Fragment-only 'URL' should be returned as-is.",
        );
        self::assertSame(
            '.test/me1',
            Url::to('.test/me1'),
            "Dot-prefixed 'URL' should be returned as-is.",
        );
        self::assertSame(
            'http://example.com/test/me1',
            Url::to('test/me1', true),
            "Relative string 'URL' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/test/me1',
            Url::to('test/me1', 'https'),
            "Relative string 'URL' with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            'https://example.com/test/test/me1',
            Url::to('@web4/test/me1', 'https'),
            "Alias-prefixed 'path' with 'https' scheme should resolve correctly.",
        );
        self::assertSame(
            '/test/me1',
            Url::to('/test/me1'),
            "Absolute 'path' URL should be returned as-is.",
        );
        self::assertSame(
            'http://example.com/test/me1',
            Url::to('/test/me1', true),
            "Absolute 'path' URL with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/test/me1',
            Url::to('/test/me1', 'https'),
            "Absolute 'path' URL with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            './test/me1',
            Url::to('./test/me1'),
            "Dot-slash relative 'URL' should be returned as-is.",
        );
        self::assertSame(
            'http://test.example.com/test/me1',
            Url::to('@web1'),
            "Absolute 'alias' URL should be returned as-is.",
        );
        self::assertSame(
            'http://test.example.com/test/me1',
            Url::to('@web1', true),
            "Absolute 'alias' URL with absolute 'scheme' should preserve original 'scheme'.",
        );
        self::assertSame(
            'https://test.example.com/test/me1',
            Url::to('@web1', 'https'),
            "Absolute 'alias' URL with 'https' scheme should replace 'scheme'.",
        );
        self::assertSame(
            'test/me2',
            Url::to('@web2'),
            "Relative 'alias' URL should be returned as-is.",
        );
        self::assertSame(
            'http://example.com/test/me2',
            Url::to('@web2', true),
            "Relative 'alias' URL with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/test/me2',
            Url::to('@web2', 'https'),
            "Relative 'alias' URL with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '/base/index.php&r=site%2Fcurrent&id=42',
            Url::to('@web3'),
            "Empty 'alias' should return currently requested 'URL'.",
        );
        self::assertSame(
            'http://example.com/base/index.php&r=site%2Fcurrent&id=42',
            Url::to('@web3', true),
            "Empty 'alias' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php&r=site%2Fcurrent&id=42',
            Url::to('@web3', 'https'),
            "Empty 'alias' with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '/test',
            Url::to('@web4'),
            "Absolute 'path' alias should be returned as-is.",
        );
        self::assertSame(
            'http://example.com/test',
            Url::to('@web4', true),
            "Absolute 'path' alias with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/test',
            Url::to('@web4', 'https'),
            "Absolute 'path' alias with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '#test',
            Url::to('@web5'),
            "Fragment 'alias' should be returned as-is.",
        );
        self::assertSame(
            'http://example.com/#test',
            Url::to('@web5', true),
            "Fragment 'alias' with absolute 'scheme' should include 'host' info.",
        );
        self::assertSame(
            'https://example.com/#test',
            Url::to('@web5', 'https'),
            "Fragment 'alias' with 'https' scheme should use 'https'.",
        );
        self::assertSame(
            '//example.com/#test',
            Url::to('@web5', ''),
            "Fragment 'alias' with empty 'scheme' should produce 'protocol-relative' URL.",
        );

        // @see https://github.com/yiisoft/yii2/issues/13156
        Yii::setAlias('@cdn', '//cdn.example.com');

        self::assertSame(
            'http://cdn.example.com/images/logo.gif',
            Url::to('@cdn/images/logo.gif', 'http'),
            "Protocol-relative 'alias' with 'http' scheme should use 'http'.",
        );
        self::assertSame(
            '//cdn.example.com/images/logo.gif',
            Url::to('@cdn/images/logo.gif', ''),
            "Protocol-relative 'alias' with empty 'scheme' should remain 'protocol-relative'.",
        );
        self::assertSame(
            'https://cdn.example.com/images/logo.gif',
            Url::to('@cdn/images/logo.gif', 'https'),
            "Protocol-relative 'alias' with 'https' scheme should use 'https'.",
        );

        Yii::setAlias('@cdn', null);

        $this->removeMockedAction();

        $this->expectException('yii\base\InvalidParamException');

        Url::to(['site/view']);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/11925
     */
    public function testToWithSuffix(): void
    {
        Yii::$app->set(
            'urlManager',
            [
                'class' => UrlManager::class,
                'enablePrettyUrl' => true,
                'showScriptName' => false,
                'cache' => null,
                'rules' => [
                    '<controller:\w+>/<id:\d+>' => '<controller>/view',
                    '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                    '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                ],
                'baseUrl' => '/',
                'scriptUrl' => '/index.php',
                'suffix' => '.html',
            ],
        );
        $url = Yii::$app->urlManager->createUrl(['/site/page', 'view' => 'about']);

        self::assertSame(
            '/site/page.html?view=about',
            $url,
            "'UrlManager' should append 'suffix' before 'query' string.",
        );

        $url = Url::to(['/site/page', 'view' => 'about']);

        self::assertSame(
            '/site/page.html?view=about',
            $url,
            "'Url::to()' should produce URL with 'suffix'.",
        );

        $output = Menu::widget(
            [
                'items' => [
                    [
                        'label' => 'Test',
                        'url' => ['/site/page', 'view' => 'about'],
                    ],
                ],
            ],
        );

        self::assertMatchesRegularExpression(
            '~<a href="/site/page.html\?view=about">~',
            $output,
            "'Menu' widget should render URL with 'suffix'.",
        );
    }

    public function testBase(): void
    {
        $this->mockAction('page', 'view', null, ['id' => 10]);

        self::assertSame(
            '/base',
            Url::base(),
            "Should return relative 'base' URL.",
        );
        self::assertSame(
            'http://example.com/base',
            Url::base(true),
            "Should return absolute 'base' URL with 'host' info.",
        );
        self::assertSame(
            'https://example.com/base',
            Url::base('https'),
            "Should return absolute 'base' URL with 'https' scheme.",
        );
        self::assertSame(
            '//example.com/base',
            Url::base(''),
            "Should return 'protocol-relative' base URL.",
        );
    }

    public function testHome(): void
    {
        self::assertSame(
            '/base/index.php',
            Url::home(),
            "Should return relative 'home' URL.",
        );
        self::assertSame(
            'http://example.com/base/index.php',
            Url::home(true),
            "Should return absolute 'home' URL with 'host' info.",
        );
        self::assertSame(
            'https://example.com/base/index.php',
            Url::home('https'),
            "Should return absolute 'home' URL with 'https' scheme.",
        );
        self::assertSame(
            '//example.com/base/index.php',
            Url::home(''),
            "Should return 'protocol-relative' home URL.",
        );
    }

    public function testCanonical(): void
    {
        $this->mockAction('page', 'view', null, ['id' => 10]);

        self::assertSame(
            'http://example.com/base/index.php?r=page%2Fview&id=10',
            Url::canonical(),
            "Should return 'canonical' URL with 'route' and action 'params'.",
        );

        $this->removeMockedAction();
    }

    public function testIsRelative(): void
    {
        self::assertTrue(
            Url::isRelative('/test/index.php'),
            "Path-only 'URL' should be considered 'relative'.",
        );
        self::assertTrue(
            Url::isRelative('index.php'),
            "Filename-only 'URL' should be considered 'relative'.",
        );
        self::assertFalse(
            Url::isRelative('//example.com/'),
            "'Protocol-relative' URL should not be considered 'relative'.",
        );
        self::assertFalse(
            Url::isRelative('http://example.com/'),
            "'HTTP' URL should not be considered 'relative'.",
        );
        self::assertFalse(
            Url::isRelative('https://example.com/'),
            "'HTTPS' URL should not be considered 'relative'.",
        );
    }

    public function testAddQueryParamsNoExistingQuery(): void
    {
        self::assertSame(
            '/path?a=1',
            Url::addQueryParams('/path', ['a' => 1]),
            "Should append 'query' params to a URL without existing 'query' string.",
        );
    }

    public function testAddQueryParamsMergeWithExisting(): void
    {
        self::assertSame(
            '/path?a=1&b=2',
            Url::addQueryParams('/path?a=1', ['b' => 2]),
            "Should merge new 'params' with existing 'query' string params.",
        );
    }

    public function testAddQueryParamsOverrideExisting(): void
    {
        self::assertSame(
            '/path?a=2',
            Url::addQueryParams('/path?a=1', ['a' => 2]),
            "Should override existing 'param' when same 'key' is provided.",
        );
    }

    public function testAddQueryParamsRemoveViaNullValue(): void
    {
        self::assertSame(
            '/path?b=2',
            Url::addQueryParams('/path?a=1&b=2', ['a' => null]),
            "Should remove 'param' when value is 'null'.",
        );
    }

    public function testAddQueryParamsRemoveAllParams(): void
    {
        self::assertSame(
            '/path',
            Url::addQueryParams('/path?a=1', ['a' => null]),
            "Should return URL without 'query' string when all 'params' are removed.",
        );
    }

    public function testAddQueryParamsFragmentPreserved(): void
    {
        self::assertSame(
            '/path?a=1&b=2#sec',
            Url::addQueryParams('/path?a=1#sec', ['b' => 2]),
            "Should preserve URL 'fragment' when adding 'params'.",
        );
    }

    public function testAddQueryParamsFragmentOnlyNoQuery(): void
    {
        self::assertSame(
            '/path?a=1#sec',
            Url::addQueryParams('/path#sec', ['a' => 1]),
            "Should add 'query' params before 'fragment' on URL with no existing 'query'.",
        );
    }

    public function testAddQueryParamsEmptyUrl(): void
    {
        self::assertSame(
            '?a=1',
            Url::addQueryParams('', ['a' => 1]),
            "Should produce 'query'-only string when URL is empty.",
        );
    }

    public function testAddQueryParamsAbsoluteUrl(): void
    {
        self::assertSame(
            'https://example.com/p?x=1&y=2',
            Url::addQueryParams('https://example.com/p?x=1', ['y' => 2]),
            "Should handle absolute 'URLs' with 'scheme'.",
        );
    }

    public function testAddQueryParamsProtocolRelativeUrl(): void
    {
        self::assertSame(
            '//cdn.example.com/p?v=3',
            Url::addQueryParams('//cdn.example.com/p', ['v' => 3]),
            "Should handle 'protocol-relative' URLs.",
        );
    }

    public function testAddQueryParamsArrayParams(): void
    {
        self::assertSame(
            '/path?arr%5Bk%5D=v',
            Url::addQueryParams('/path', ['arr' => ['k' => 'v']]),
            "Should support nested 'array' parameters.",
        );
    }

    public function testAddQueryParamsEmptyParamsArray(): void
    {
        self::assertSame(
            '/path?a=1',
            Url::addQueryParams('/path?a=1', []),
            "Should return URL unchanged when 'params' array is empty.",
        );
    }

    public function testAddQueryParamsUrlWithPort(): void
    {
        self::assertSame(
            'http://example.com:8080/path?a=1',
            Url::addQueryParams('http://example.com:8080/path', ['a' => 1]),
            "Should handle URLs with 'port' numbers.",
        );
    }

    public function testAddQueryParamsNestedNullRemoval(): void
    {
        self::assertSame(
            '/path?arr%5Bb%5D=2',
            Url::addQueryParams('/path?arr[a]=1&arr[b]=2', ['arr' => ['a' => null]]),
            "Should recursively remove nested 'params' set to 'null'.",
        );
    }

    public function testAddQueryParamsNestedNullRemovalRemovesParentKey(): void
    {
        self::assertSame(
            '/path',
            Url::addQueryParams('/path?arr[a]=1&arr[b]=2', ['arr' => ['a' => null, 'b' => null]]),
            "Should remove parent 'key' when all nested 'params' are set to 'null'.",
        );
    }

    public function testAddQueryParamsSpecialCharacters(): void
    {
        self::assertSame(
            '/path?msg=hello+world&key=a%26b%3Dc',
            Url::addQueryParams('/path', ['msg' => 'hello world', 'key' => 'a&b=c']),
            "Should properly encode special characters in 'param' values.",
        );
    }

    public function testRemember(): void
    {
        Yii::$app->getUser()->login(UserIdentity::findIdentity('user1'));

        Url::remember('test');

        self::assertSame(
            'test',
            Yii::$app->getUser()->getReturnUrl(),
            "Should set 'returnUrl' via 'User' component.",
        );
        self::assertSame(
            'test',
            Yii::$app->getSession()->get(Yii::$app->getUser()->returnUrlParam),
            "Should store 'returnUrl' in 'session' via 'User' returnUrlParam.",
        );

        Yii::$app->getUser()->setReturnUrl(null);
        Url::remember('test', 'remember-test');

        self::assertSame(
            'test',
            Yii::$app->getSession()->get('remember-test'),
            "Should store named URL in 'session' with given 'key'.",
        );
    }
}
