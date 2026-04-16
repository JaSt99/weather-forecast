<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Listener;

use App\Http\Listener\ApiKeyListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiKeyListenerTest extends TestCase
{
    private ApiKeyListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ApiKeyListener('secret-key');
    }

    #[Test]
    public function validApiKeyAllowsRequest(): void
    {
        $event = $this->createEvent('/api/v1/weather/forecast/city', ['X-API-Key' => 'secret-key']);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function missingApiKeyThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        ($this->listener)($this->createEvent('/api/v1/weather/forecast/city'));
    }

    #[Test]
    public function invalidApiKeyThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        ($this->listener)($this->createEvent('/api/v1/weather/forecast/city', ['X-API-Key' => 'wrong-key']));
    }

    #[Test]
    public function unauthorizedExceptionContainsMessage(): void
    {
        $this->expectExceptionMessage('Unauthorized.');

        ($this->listener)($this->createEvent('/api/v1/cities'));
    }

    #[Test]
    public function nonV1PathIsNotProtected(): void
    {
        $event = $this->createEvent('/api/doc');

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function healthCheckPathIsNotProtected(): void
    {
        $event = $this->createEvent('/api/health');

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    private function createEvent(string $path, array $headers = []): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
