<?php

namespace App\Controller;

use App\Entity\Consultatii;
use App\Entity\Facturi;
use App\Entity\Pacienti;
use App\PDF\Service\PdfService;
use App\Services\AdminService;
use App\Services\EmailService;
use App\Services\FacturaService;
use App\Validator\FacturaConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Output\Destination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FacturiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private AuthorizationCheckerInterface $authorizationChecker,
        private FacturaService $facturaService,
        private AdminService $adminService,
        private ValidatorInterface $validator,
        private FacturaConstraints $constraint,
        private PdfService $pdfService
    ) {}

    #[Route('/facturi', name: 'facturi', methods: ['GET'])]
    public function facturi()
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new Facturi())) {
            throw new AccessDeniedException();
        }

        return $this->render('@templates/facturi.html.twig');
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => $request->query->all('order')[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length'),
            ]
        );

        $facturi = $this->em->getRepository(Facturi::class)->getAllFacturi($filter);

        return new JsonResponse([
            'data' => $facturi['facturi'],
            'recordsTotal' => intval($facturi['total']),
            'recordsFiltered' => intval($facturi['total']),
        ]);
    }

    #[Route('/facturi/salveaza_factura', name: 'salveaza_factura', methods: ['POST'])]
    public function salveazaFactura(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('ADD', new Facturi())) {
            throw new AccessDeniedException();
        }

        parse_str($request->request->get('form'), $formData);

        $invoice = $this->facturaService->prepareInvoice($formData);

        $errors = $this->validator->validate($invoice, $this->constraint);

        if (count($errors)) {
            $messages = $this->adminService->buildValidationErrors($errors);

            return new JsonResponse(
                [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => $this->translator->trans('Failed operation').' '.$messages,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->getRepository(Facturi::class)->saveInvoice($invoice);

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => $this->translator->trans('Successful operation'),
            ]
        );
    }

    #[Route('/facturi/pdf_factura', name: 'pdf_factura', methods: ['POST'])]
    public function pdf(Request $request): JsonResponse
    {
        $this->pdfService->printToPdf($request->request->get('factura_id'), 'factura.html.twig');

        return new JsonResponse([
            Response::HTTP_OK,
            $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/facturi/trimite_email_factura", name: "trimite_email_factura", methods: ["POST"])]
    public function trimiteEmailFactura(Request $request, EmailService $emailService): Response
    {
        $pdf = $this->pdfService->printToPdf(
            $request->request->get('id'),
            'factura.html.twig',
            ['destination' => Destination::STRING_RETURN]
        );

        $emailData = [
            'recipient' => $request->request->get('email'),
            'subject' => $this->translator->trans('Invoice attached'),
            'template' => 'emails/email_factura.html.twig',
            'att' => [
                'doc' => $pdf,
                'name' => 'factura.pdf',
                'mime' => 'application/pdf'
            ]
        ];

        $result = $emailService->sendEmail($emailData);

        return new JsonResponse(['message' => $result['message'], 'status' => $result['status']]);
    }

    #[Route('/storneaza_factura', name: 'storneaza_factura', methods: ['POST'])]
    public function storneaza(Request $request): JsonResponse
    {
        if (!$this->authorizationChecker->isGranted('ADD', new Facturi())) {
            throw new AccessDeniedException();
        }

        $factura = $this->em->getRepository(Facturi::class)->find($request->request->get('factura_id'));

        if ($factura->getStornare() instanceof Facturi) {
            return new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $this->translator->trans('Invoice already credited'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->getRepository(Facturi::class)->storneaza($factura);

        return new JsonResponse([
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ], Response::HTTP_OK);
    }

    #[Route('/facturi/consultatii_nefacturate', name: 'consultatii_nefacturate', methods: ['GET'])]
    public function consultatiiNefacturate()
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new Facturi())) {
            throw new AccessDeniedException();
        }

        return $this->render('@templates/consultatii_nefacturate.html.twig');
    }

    #[Route('/facturi/consultatii_nefacturate_list', name: 'consultatii_nefacturate_list', methods: ['GET'])]
    public function consultatiiNefacturateList(Request $request)
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new Facturi())) {
            throw new AccessDeniedException();
        }

        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => $request->query->all('order')[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length'),
            ]
        );

        $data = $this->em->getRepository(Pacienti::class)->getPacientiConsultatiiNefacturate($filter);

        return new JsonResponse([
            'data' => $data['pacienti'] ?? [],
            'recordsTotal' => intval($data['total']),
            'recordsFiltered' => intval($data['total'])
        ]);
    }

    #[Route('/facturi/consultatii_nefacturate_pacient', name: 'consultatii_nefacturate_pacient', methods: ['GET'])]
    public function consultatiiNefacturatePacient(Request $request)
    {
        $serviciiPacient = $this->em->getRepository(Consultatii::class)
            ->getConsultatiiNefacturatePacient($request->query->get('pacient_id'));

        return new JsonResponse([
            'serviciiPacient' => $serviciiPacient,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }
}