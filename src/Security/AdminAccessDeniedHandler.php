<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

final class AdminAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        if (!preg_match('{^/admin(?:/|$)}', $request->getPathInfo()) || $request->isXmlHttpRequest() || $request->getPreferredFormat() !== 'html') {
            return null;
        }

        return new Response($this->twig->render('security/access_denied.html.twig', [
            'modal_return_url' => $this->urls->generate('app_home'),
            'modal_flashes' => [[
                'type' => 'error',
                'message' => 'This area is reserved for administrators. You can continue shopping in the store.',
            ]],
        ]), Response::HTTP_FORBIDDEN);
    }
}
