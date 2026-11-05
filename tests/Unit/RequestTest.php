<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Core\Request;
use PHPUnit\Framework\TestCase;

/** Shared hosts (FastCGI/PHP-FPM) pass Content-Type as CONTENT_TYPE, not HTTP_CONTENT_TYPE. */
final class RequestTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testContentTypeFromFastCgiVariableIsRecognised(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/exam/x/answer', 'SCRIPT_NAME' => '/index.php', 'CONTENT_TYPE' => 'application/json'];
        $request = Request::fromGlobals();
        self::assertSame('application/json', $request->header('content-type'));
        self::assertTrue($request->wantsJson());
    }

    public function testJsonBodyIsReadWhenTheHeaderWasStripped(): void
    {
        $request = new \ReflectionClass(Request::class);
        $instance = $request->newInstanceWithoutConstructor();
        foreach (['body' => '{"question_id":5,"selected_option":"A"}', 'headers' => [], 'path' => '/exam/x/answer'] as $prop => $value) {
            $p = $request->getProperty($prop);
            $p->setValue($instance, $value);
        }
        self::assertSame(['question_id' => 5, 'selected_option' => 'A'], $instance->json());
    }
}
