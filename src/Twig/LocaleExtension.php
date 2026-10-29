<?php

declare(strict_types=1);

namespace App\Twig;

use App\Localization\LocalePolicy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocaleExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requests, #[Autowire(service: 'translator')] private readonly TranslatorBagInterface $translator)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('locale_bootstrap', $this->bootstrap(...))];
    }

    public function bootstrap(): array
    {
        $messages = [];
        foreach (LocalePolicy::SUPPORTED as $locale) {
            $messages[$locale] = $this->translator->getCatalogue($locale)->all('messages');
        }

        return [
            'locale' => $this->requests->getCurrentRequest()?->getLocale() ?? LocalePolicy::DEFAULT,
            'fallbackLocale' => LocalePolicy::DEFAULT,
            'supportedLocales' => LocalePolicy::SUPPORTED,
            'messages' => $messages,
        ];
    }
}
