<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Localization\LocalePolicy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private LocalePolicy $locales)
    {
    }

    public static function getSubscribedEvents(): array
    {
        // After routing (32), before Symfony's LocaleListener (16) and security.
        return [KernelEvents::REQUEST => ['onRequest', 20], KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getRequest()->setLocale($this->locales->resolve($event->getRequest()));
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $response = $event->getResponse();
            $response->headers->set('Content-Language', $event->getRequest()->getLocale());
            $response->setVary(['Cookie', 'Accept-Language'], false);
        }
    }
}
