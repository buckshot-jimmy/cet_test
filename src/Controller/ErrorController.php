<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErrorController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator) {}

    #[Route("/error", name: "error", methods: ["GET", "POST"])]
    public function show(Request $request): Response
    {
        $exception = $request->attributes->get('exception');

        $cetCodes = [2001, 4001, 4041, 5001, 4009];
        $exCode = $exception->getCode();

        $message = $this->translator->trans($exception->getMessage(), [], 'messages');

        if (!in_array($exCode, $cetCodes)) {
            $exCode = Response::HTTP_BAD_REQUEST;
            $message = $this->translator->trans('An error occured');
        }

        $status = match ($exCode) {
            2001 => Response::HTTP_CREATED,
            4001 => Response::HTTP_BAD_REQUEST,
            4041 => Response::HTTP_NOT_FOUND,
            5001 => Response::HTTP_INTERNAL_SERVER_ERROR,
            4009 => Response::HTTP_CONFLICT,
            default => Response::HTTP_BAD_REQUEST
        };

        $responseData =  [
            'status_code' => $status,
            'message'     => $message,
        ];

        if (!$request->isXmlHttpRequest()) {
            return $this->render('@exception/exception.html.twig', $responseData);
        }

        return new JsonResponse($responseData);
    }
}