<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Listener;

use App\Http\Listener\JsonExceptionListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class JsonExceptionListenerTest extends TestCase
{
    private JsonExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new JsonExceptionListener();
    }

    #[Test]
    public function httpExceptionReturnsJsonWithCorrectStatusCode(): void
    {
        $event = $this->createEvent(new NotFoundHttpException('Route not found.'));

        ($this->listener)($event);

        $response = $event->getResponse();
        assert($response instanceof JsonResponse);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('{"message":"Route not found."}', $response->getContent());
    }

    #[Test]
    public function httpExceptionPassesHeadersToResponse(): void
    {
        $event = $this->createEvent(
            new UnprocessableEntityHttpException('Invalid.', headers: ['X-Custom' => 'value']),
        );

        ($this->listener)($event);

        $response = $event->getResponse();
        assert($response instanceof JsonResponse);
        self::assertSame('value', $response->headers->get('X-Custom'));
    }

    #[Test]
    public function genericExceptionReturns500WithGenericMessage(): void
    {
        $event = $this->createEvent(new \RuntimeException('DB connection failed.'));

        ($this->listener)($event);

        $response = $event->getResponse();
        assert($response instanceof JsonResponse);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('{"message":"Internal Server Error"}', $response->getContent());
    }

    #[Test]
    public function genericExceptionDoesNotExposeInternalMessage(): void
    {
        $event = $this->createEvent(new \RuntimeException('secret internal details'));

        ($this->listener)($event);

        $response = $event->getResponse();
        assert($response instanceof JsonResponse);
        self::assertStringNotContainsString('secret', (string) $response->getContent());
    }

    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
