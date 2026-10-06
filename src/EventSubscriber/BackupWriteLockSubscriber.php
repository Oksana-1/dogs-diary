<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Coordinates HTTP writes with the local backup capture on our single replica. */
final class BackupWriteLockSubscriber implements EventSubscriberInterface
{
    /** @var resource|null */
    private $lock;

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/backup')]
        private readonly string $lockDirectory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Acquire before authentication/controllers can modify database or files.
            KernelEvents::REQUEST => ['onRequest', 2048],
            KernelEvents::FINISH_REQUEST => ['onFinishRequest', -2048],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->isMethodSafe()) {
            return;
        }

        if (!is_dir($this->lockDirectory) && !@mkdir($this->lockDirectory, 0770, true) && !is_dir($this->lockDirectory)) {
            $this->unavailable($event);

            return;
        }

        $handle = @fopen($this->lockDirectory.'/writes.lock', 'c');
        if (false === $handle) {
            $this->unavailable($event);

            return;
        }
        if (!flock($handle, LOCK_SH | LOCK_NB)) {
            fclose($handle);
            $this->unavailable($event);

            return;
        }

        $this->lock = $handle;
    }

    public function onFinishRequest(FinishRequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->release();
        }
    }

    public function __destruct()
    {
        $this->release();
    }

    private function release(): void
    {
        if (null !== $this->lock) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
            $this->lock = null;
        }
    }

    private function unavailable(RequestEvent $event): void
    {
        $message = 'Changes are temporarily unavailable. Please try again shortly.';
        $headers = ['Retry-After' => '30', 'Cache-Control' => 'no-store'];
        $path = $event->getRequest()->getPathInfo();
        $event->setResponse('/api' === $path || str_starts_with($path, '/api/')
            ? new JsonResponse(['error' => ['code' => 'temporarily_unavailable', 'message' => $message]], 503, $headers)
            : new Response($message, 503, $headers));
    }
}
