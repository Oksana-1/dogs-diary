<?php

declare(strict_types=1);

namespace App\Localization;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

final class LocalePolicy
{
    public const DEFAULT = 'en';
    public const SUPPORTED = ['en', 'uk'];
    public const COOKIE = 'diary_locale';
    public const COOKIE_LIFETIME = 31536000;

    public function resolve(Request $request): string
    {
        if ($request->cookies->has(self::COOKIE)) {
            $stored = $request->cookies->all()[self::COOKIE];

            return in_array($stored, self::SUPPORTED, true) ? $stored : self::DEFAULT;
        }

        // HttpFoundation orders preferences by quality; zero explicitly refuses a language.
        foreach (AcceptHeader::fromString($request->headers->get('Accept-Language'))->all() as $preference) {
            if ($preference->getQuality() <= 0) {
                continue;
            }
            $language = $preference->getValue();
            $base = strtolower(explode('_', str_replace('-', '_', $language))[0]);
            if (in_array($base, self::SUPPORTED, true)) {
                return $base;
            }
        }

        return self::DEFAULT;
    }
}
