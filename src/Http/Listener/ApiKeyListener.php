<?php

declare(strict_types=1);

namespace App\Http\Listener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
readonly class ApiKeyListener
{
    public function __construct(
        private string $apiKey,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return;
        }

        if ($request->headers->get('X-API-Key') === $this->apiKey) {
            return;
        }

        throw new UnauthorizedHttpException('X-API-Key', 'Unauthorized.');
    }
}
