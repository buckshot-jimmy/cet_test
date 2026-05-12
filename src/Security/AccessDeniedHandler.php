<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private TranslatorInterface $translator, private Environment $twig) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'message' => $this->translator->trans('Access denied')],
                Response::HTTP_FORBIDDEN
            );
        }

        return new Response($this->twig->render('@exception/exception.html.twig', [
            'status_code' => Response::HTTP_FORBIDDEN,
            'message' => $this->translator->trans('Access denied'),
        ]));
    }
}