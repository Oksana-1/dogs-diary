<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Http\DeferredMediaResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class DeferredMediaResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(#[Autowire(service: 'monolog.logger.runtime')] private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // Security persists its token first; AbstractSessionListener closes at -1000.
        return [KernelEvents::RESPONSE => ['resolve', -1100]];
    }

    public function resolve(ResponseEvent $event): void
    {
        $pending = $event->getResponse();
        if (!$event->isMainRequest() || !$pending instanceof DeferredMediaResponse) {
            return;
        }
        $started = hrtime(true);
        $outcome = 'error';
        try {
            $response = ($pending->resolve)();
            if (null === $response) {
                $outcome = 'missing';
                throw new NotFoundHttpException('Media file not found');
            }
            // Preserve security headers and cookies already produced by the response listeners.
            // Media-specific headers (Location, content type, ranges, no-referrer) win.
            foreach ($pending->headers->all() as $name => $values) {
                if (!in_array($name, ['content-type', 'content-length', 'set-cookie'], true) && !$response->headers->has($name)) {
                    $response->headers->set($name, $values);
                }
            }
            foreach ($pending->headers->getCookies() as $cookie) {
                $response->headers->setCookie($cookie);
            }
            $response->prepare($event->getRequest());
            $event->setResponse($response);
            $outcome = 'success';
        } finally {
            $this->logger->info('Private media lookup completed.', [
                'duration_ms' => round((hrtime(true) - $started) / 1e6, 2),
                'outcome' => $outcome,
            ]);
        }
    }
}
