<?php

declare(strict_types=1);

namespace App\Http\Listener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class JsonExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['message' => $exception->getMessage()],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));

            return;
        }

        $event->setResponse(
            new JsonResponse(
            ['message' => 'Internal Server Error'],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
    }
}
