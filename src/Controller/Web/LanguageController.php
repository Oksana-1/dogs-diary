<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Localization\LocalePolicy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LanguageController extends AbstractController
{
    #[Route('/language', name: 'app_language', methods: ['POST'])]
    public function switch(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('language', $request->request->getString('_csrf_token'))) {
            return new Response('Invalid language form token.', Response::HTTP_FORBIDDEN);
        }

        $locale = $request->request->getString('locale');
        if (!in_array($locale, LocalePolicy::SUPPORTED, true)) {
            return new Response('Unsupported language.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $returnTo = $request->request->getString('returnTo');
        // Only root-relative paths; reject protocol-relative URLs and browser URL normalization tricks.
        if (!str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//') || preg_match('/[\\\\\x00-\x20\x7f]/', $returnTo)) {
            $returnTo = $this->generateUrl('app_main');
        }

        $response = $this->redirect($returnTo, Response::HTTP_SEE_OTHER);
        $response->headers->setCookie(Cookie::create(LocalePolicy::COOKIE)
            ->withValue($locale)
            ->withExpires(time() + LocalePolicy::COOKIE_LIFETIME)
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX));

        return $response;
    }
}
