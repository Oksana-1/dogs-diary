<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\Response;

/** Resolved after Symfony has persisted authentication and closed the session. */
final class DeferredMediaResponse extends Response
{
    public function __construct(public readonly \Closure $resolve)
    {
        parent::__construct('', 200, ['Cache-Control' => 'private, no-store']);
    }
}
