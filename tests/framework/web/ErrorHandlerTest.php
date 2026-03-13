<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use yii\BaseYii;
use yii\base\ErrorException;
use yii\web\Application;
use Yii;
use yii\web\ErrorHandlerRenderEvent;
use yii\web\NotFoundHttpException;
use yii\web\View;
use yiiunit\TestCase;

#[Group('web')]
#[Group('error-handler')]
class ErrorHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWebApplication([
            'controllerNamespace' => 'yiiunit\\data\\controllers',
            'components' => [
                'errorHandler' => [
                    'class' => ErrorHandler::class,
                    'errorView' => '@yiiunit/data/views/errorHandler.php',
                    'exceptionView' => '@yiiunit/data/views/errorHandlerForAssetFiles.php',
                ],
            ],
        ]);
    }

    public function testCorrectResponseCodeInErrorView(): void
    {
        /** @var ErrorHandler $handler */

        $handler = Yii::$app->getErrorHandler();

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException',
            [new NotFoundHttpException('This message is displayed to end user')]
        );

        ob_get_clean();

        $out = Yii::$app->response->data;

        self::assertSame(
            <<<TEXT
            Code: 404
            Message: This message is displayed to end user
            Exception: yii\web\NotFoundHttpException
            TEXT,
            $out,
            'Error view should render expected status code, message and exception class.',
        );
    }

    public function testFormatRaw(): void
    {
        Yii::$app->response->format = yii\web\Response::FORMAT_RAW;

        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException', [new Exception('Test Exception')],
        );

        $out = ob_get_clean();

        $this->assertStringContainsString(
            'Test Exception',
            $out,
            'Raw response output should contain the exception message.',
        );
        self::assertIsString(
            Yii::$app->response->data,
            'Raw response data should be a string.',
        );
        self::assertStringContainsString(
            "Exception 'Exception' with message 'Test Exception'",
            Yii::$app->response->data,
            'Raw response data should contain the serialized exception details.',
        );
    }

    public function testFormatXml(): void
    {
        Yii::$app->response->format = yii\web\Response::FORMAT_XML;

        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException',
            [new Exception('Test Exception')],
        );

        $out = ob_get_clean();

        $this->assertStringContainsString(
            'Test Exception',
            $out,
            'XML response output should contain the exception message.',
        );

        $outArray = Yii::$app->response->data;

        self::assertIsArray(
            Yii::$app->response->data,
            'XML response data should be converted to an array payload.',
        );
        self::assertEquals(
            'Exception',
            $outArray['name'],
            'XML payload should contain the expected exception name.',
        );
        self::assertEquals(
            'Test Exception',
            $outArray['message'],
            'XML payload should contain the expected exception message.',
        );
        self::assertArrayHasKey(
            'code',
            $outArray,
            'XML payload should include the exception code field.',
        );
        self::assertEquals(
            'Exception',
            $outArray['type'],
            'XML payload should include the exception class type.',
        );
        self::assertStringContainsString(
            'ErrorHandlerTest.php',
            $outArray['file'],
            'XML payload should include the file where the exception originated.',
        );
        self::assertArrayHasKey(
            'stack-trace',
            $outArray,
            'XML payload should include stack-trace details in debug mode.',
        );
        self::assertArrayHasKey(
            'line',
            $outArray,
            'XML payload should include the exception line number.',
        );
    }

    public function testClearAssetFilesInErrorView(): void
    {
        Yii::$app->getView()->registerJsFile('somefile.js');

        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException',
            [new Exception('Some Exception')],
        );

        ob_get_clean();

        $out = Yii::$app->response->data;

        self::assertSame(
            "Exception View\n",
            $out,
            'Error view rendering should clear previously registered asset files.',
        );
    }

    public function testClearAssetFilesInErrorActionView(): void
    {
        Yii::$app->getErrorHandler()->errorAction = 'test/error';
        Yii::$app->getView()->registerJs("alert('hide me')", View::POS_END);

        /** @var ErrorHandler $handler */
        $handler = Yii::$app->getErrorHandler();

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException', [new NotFoundHttpException()],
        );

        ob_get_clean();

        $out = Yii::$app->response->data;

        self::assertStringNotContainsString(
            '<script',
            $out,
            'Error action view should not keep previously registered script tags.',
        );
    }

    public function testAfterRenderEventCanModifyOutput(): void
    {
        $handler = Yii::$app->getErrorHandler();

        $exception = new Exception('Some Exception');
        $actualException = null;

        $handler->on(
            \yii\web\ErrorHandler::EVENT_AFTER_RENDER,
            static function (ErrorHandlerRenderEvent $event) use (&$actualException): void {
                $actualException = $event->exception;
                $event->output .= "\n<!--after-render-->";
            },
        );

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException',
            [$exception],
        );

        ob_get_clean();

        $this->assertSame(
            $exception,
            $actualException,
            "Rendered event should expose the same exception instance that was passed to 'renderException()'.",
        );
        $this->assertStringContainsString(
            '<!--after-render-->',
            Yii::$app->response->data,
            'Event handler should be able to append custom markup to the HTML response.',
        );
    }

    public function testAfterRenderEventCanModifyOutputInErrorActionView(): void
    {
        $handler = Yii::$app->getErrorHandler();

        $handler->errorAction = 'test/error';

        $exception = new NotFoundHttpException('Resource not found');
        $actualException = null;

        $handler->on(
            \yii\web\ErrorHandler::EVENT_AFTER_RENDER,
            static function (ErrorHandlerRenderEvent $event) use (&$actualException): void {
                $actualException = $event->exception;
                $event->output .= "\n<!--after-render-error-action-->";
            },
        );

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException',
            [$exception],
        );

        ob_get_clean();

        $this->assertSame(
            $exception,
            $actualException,
            "Rendered event should expose the same exception instance for the 'errorAction' HTML path.",
        );
        $this->assertStringContainsString(
            '<!--after-render-error-action-->',
            Yii::$app->response->data,
            "Event handler should be able to append custom markup when output is rendered via 'errorAction'.",
        );
    }

    public function testAfterRenderEventCanModifyOutputForPhpErrors(): void
    {
        $handler = Yii::$app->getErrorHandler();

        $exception = new ErrorException('PHP Warning', E_WARNING, E_WARNING, __FILE__, __LINE__);

        $handler->exception = $exception;

        $handler->on(
            \yii\web\ErrorHandler::EVENT_AFTER_RENDER,
            static function (ErrorHandlerRenderEvent $event): void {
                $event->output .= "\n<!--php-error-after-render-->";
            },
        );

        ob_start(); // suppress response output

        $this->invokeMethod(
            $handler,
            'renderException',
            [$exception],
        );

        ob_get_clean();

        $this->assertStringContainsString(
            '<!--php-error-after-render-->',
            Yii::$app->response->data,
            'Event handler should be able to modify output for PHP ErrorException rendering path.',
        );
    }

    public function testRenderCallStackItem(): void
    {
        $handler = Yii::$app->getErrorHandler();

        $handler->traceLine = <<<HTML
        <a href="netbeans://open?file={file}&line={line}">{html}</a>
        HTML;

        $file = BaseYii::getAlias('@yii/web/Application.php');

        $out = $handler->renderCallStackItem($file, 63, Application::class, null, null, null);

        $this->assertStringContainsString(
            "<a href=\"netbeans://open?file=$file&line=63\">",
            $out,
            'Rendered call stack item should contain IDE trace link with file and line placeholders.',
        );
    }

    public static function dataHtmlEncode(): array
    {
        return [
            [
                "a \t=<>&\"'\x80`\n",
                "a \t=&lt;&gt;&amp;&quot;&apos;�`\n",
            ],
            [
                '<b>test</b>',
                '&lt;b&gt;test&lt;/b&gt;',
            ],
            [
                '"hello"',
                '&quot;hello&quot;',
            ],
            [
                "'hello world'",
                '&apos;hello world&apos;',
            ],
            [
                'Chip&amp;Dale',
                'Chip&amp;amp;Dale',
            ],
            [
                "\t\$x=24;",
                "\t\$x=24;",
            ],
        ];
    }

    /**
     * @dataProvider dataHtmlEncode
     */
    public function testHtmlEncode($text, $expected): void
    {
        $handler = Yii::$app->getErrorHandler();

        $this->assertSame(
            $expected,
            $handler->htmlEncode($text),
            'Should transform input text according to expected HTML entity encoding.',
        );
    }

    public function testHtmlEncodeWithUnicodeSequence(): void
    {
        $handler = Yii::$app->getErrorHandler();

        $text = "a \t=<>&\"'\x80\u{20bd}`\u{000a}\u{000c}\u{0000}";
        $expected = "a \t=&lt;&gt;&amp;&quot;&apos;�₽`\n\u{000c}\u{0000}";

        $this->assertSame(
            $expected,
            $handler->htmlEncode($text),
            'Should preserve supported Unicode characters and normalize invalid byte sequences.',
        );
    }
}

class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @return bool if simple HTML should be rendered
     */
    protected function shouldRenderSimpleHtml()
    {
        return false;
    }
}
