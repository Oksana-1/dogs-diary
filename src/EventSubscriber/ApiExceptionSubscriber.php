<?php

namespace App\EventSubscriber;

use App\Application\Media\Exception\MediaValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        if ($exception instanceof AccessDeniedException) {
            // Let the security exception listener invoke the configured entry point.
            return;
        }

        $status = $this->statusCode($exception);
        $error = [
            'code' => $this->errorCode($status),
            'message' => $this->message($exception, $status),
        ];

        if ($validationException = $this->findValidationException($exception)) {
            $error['message'] = 'The request contains invalid values.';
            $error['violations'] = [];

            foreach ($validationException->getViolations() as $violation) {
                $error['violations'][] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
        } elseif ($exception instanceof MediaValidationException && Response::HTTP_UNPROCESSABLE_ENTITY === $status) {
            $error['violations'] = [[
                'field' => $exception->getField(),
                'message' => $exception->getMessage(),
            ]];
        }

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error('Unhandled API exception.', [
                'exception' => $exception,
                'path' => $event->getRequest()->getPathInfo(),
            ]);
        }

        $response = new JsonResponse(['error' => $error], $status);
        if ($exception instanceof HttpExceptionInterface) {
            $response->headers->replace($exception->getHeaders() + $response->headers->all());
        }

        $event->setResponse($response);
    }

    private function statusCode(\Throwable $exception): int
    {
        return match (true) {
            $exception instanceof MediaValidationException => $exception->getStatusCode(),
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    private function errorCode(int $status): string
    {
        return match ($status) {
            Response::HTTP_BAD_REQUEST => 'invalid_request',
            Response::HTTP_UNAUTHORIZED => 'authentication_required',
            Response::HTTP_FORBIDDEN => 'access_denied',
            Response::HTTP_NOT_FOUND => 'resource_not_found',
            Response::HTTP_METHOD_NOT_ALLOWED => 'method_not_allowed',
            Response::HTTP_CONFLICT => 'conflict',
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE => 'payload_too_large',
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE => 'unsupported_media_type',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'validation_failed',
            Response::HTTP_TOO_MANY_REQUESTS => 'too_many_requests',
            Response::HTTP_INTERNAL_SERVER_ERROR => 'internal_error',
            default => 'request_failed',
        };
    }

    private function message(\Throwable $exception, int $status): string
    {
        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            return 'An unexpected error occurred.';
        }

        $message = trim($exception->getMessage());

        return '' !== $message ? $message : (Response::$statusTexts[$status] ?? 'Request failed');
    }

    private function findValidationException(\Throwable $exception): ?ValidationFailedException
    {
        do {
            if ($exception instanceof ValidationFailedException) {
                return $exception;
            }
        } while ($exception = $exception->getPrevious());

        return null;
    }
}
