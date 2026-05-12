<?php

namespace App\Controller;

use App\Entity\Programari;
use App\Entity\User;
use App\Form\PatientCancelAppointmentFormType;
use App\Services\AdminService;
use App\Services\EmailService;
use App\Validator\ProgramareConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProgramariController extends AbstractController
{
    const ROL_MEDIC = 'ROLE_Medic';
    const ROL_PSIHOLOG = 'ROLE_Psiholog';

    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private ValidatorInterface $validator,
        private ProgramareConstraints $programareConstraints,
        private AdminService $adminService,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    #[Route("/programari", name: "programari")]
    public function programari()
    {
        $medici = $this->em->getRepository(User::class)->getAllMedici();

        $medicLogat = $this->getUser();

        return $this->render('@templates/programari.html.twig', [
            'medici' => $medici,
            'medicLogat' =>  in_array($medicLogat->getRole()->getDenumire(), [self::ROL_MEDIC, self::ROL_PSIHOLOG])
                ? " - " . $medicLogat->getNume() . " " . $medicLogat->getPrenume()
                : ""
        ]);
    }

    #[Route("/programari/list_programari", name: "list_programari", methods: ["GET"])]
    public function list(Request $request) : Response
    {
        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => ($request->query->all('order'))[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length')
            ],
            ['propertyFilters' => [
                0 => ['preturi' => ['sters' => false]],
                1 => ['medic' => ['sters' => false]]]
            ]
        );

        $loggedUser = $this->getUser();

        if (in_array($loggedUser->getRole()->getDenumire(), [self::ROL_MEDIC, self::ROL_PSIHOLOG])) {
            $filter['propertyFilters'][] = ['preturi' => ['medic' => $loggedUser->getId()]];
        }

        $programari = $this->em->getRepository(Programari::class)->getAllProgramari($filter);

        return new JsonResponse([
            'data' => $programari['programari'],
            'recordsTotal' => intval($programari['total']),
            'recordsFiltered' => intval($programari['total']),
        ]);
    }

    #[Route("/programari/add_edit_programare", name: "add_edit_programare", methods: ["POST"])]
    public function adaugaProgramare(Request $request): Response
    {
        parse_str($request->request->get('form'), $formData);

        $attribute = 'ADD';
        $subject = new Programari();

        if ($formData['programare_id']) {
            $attribute = 'EDIT';
            $subject = $this->em->getRepository(Programari::class)->findOneBy(['id' => $formData['programare_id']]);
        }

        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException();
        }

        $errors = $this->validator->validate($formData, $this->programareConstraints);

        if (count($errors)) {
            $messages = $this->adminService->buildValidationErrors($errors);

            return new JsonResponse(
                [
                    'status'  => Response::HTTP_BAD_REQUEST,
                    'message' => $this->translator->trans("Failed operation") . ' ' . $messages,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->getRepository(Programari::class)->saveProgramare($formData, $this->getUser());

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/programari/get_programare", name: "get_programare", methods: ["GET"])]
    public function getProgramare(Request $request) : Response
    {
        $data = $this->em->getRepository(Programari::class)->getProgramare($request->query->get('id'));

        return new JsonResponse([
            'programareData' => $data,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/programari/anuleaza_programare", name: "anuleaza_programare", methods: ["POST"])]
    public function anuleazaProgramare(Request $request) : Response
    {
        $subject = $this->em->getRepository(Programari::class)->findOneBy(['id' => $request->request->get('id')]);

        if (!$this->authorizationChecker->isGranted('CANCEL', $subject)) {
            throw new AccessDeniedException();
        }

        $this->em->getRepository(Programari::class)->cancelProgramare($request->request->get('id'));

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/programari/pacient_anuleaza_programare", name: "pacient_anuleaza_programare", methods: ["GET", "POST"])]
    public function pacientAnuleazaProgramare(Request $request) : Response
    {
        $form = $this->createForm(PatientCancelAppointmentFormType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            $programareId = $request->query->get('programareId');
            $programare = $this->em->getRepository(Programari::class)->findOneBy(['id' => $programareId]);
        }
        
        if ($form->isSubmitted()) {
            $formData = $request->request->all()['patient_cancel_appointment_form'];

            if (!$this->isCsrfTokenValid('patient_cancel_appointment_form', $formData['_token'])) {
                $this->addFlash('danger', $this->translator->trans('Invalid token'));

                return $this->redirect($request->headers->get('referer'));
            }

            $this->em->getRepository(Programari::class)->cancelProgramare($request->request->get('programareId'));

            $this->addFlash('success', $this->translator->trans('Appointment cancelled'));

            return $this->redirect($request->headers->get('referer'));
        }

        return $this->render('@templates/request/cancel_programare.html.twig', [
            'data' => $programare->getData()->format('d-m-Y'),
            'ora' => $programare->getOra()->format('H:i'),
            'programareId' => $programareId,
            'form' => $form->createView(),
        ]);
    }

    #[Route("/programari/check_availability", name: "check_availability", methods: ["GET"])]
    public function verificaDisponibilitate(Request $request) : Response
    {
        parse_str($request->query->get('form'), $formData);

        $free = $this->em->getRepository(Programari::class)->checkAvailability($formData);

        if (!$free) {
            throw new \Exception($this->translator->trans("Unavailable time for doctor"), 4001);
        }

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/programari/trimite_email_programare", name: "trimite_email_programare", methods: ["POST"])]
    public function trimiteEmailProgramare(Request $request, EmailService $emailService): Response
    {
        $programareId = $request->request->get('id');
        $programare = $this->em->getRepository(Programari::class)->findOneBy(['id' => $programareId]);

        $emailData = [
            'recipient' => $programare->getPacient()->getEmail(),
            'subject' => $this->translator->trans('Consultation appointment'),
            'template' => 'emails/email_programare.html.twig',
            'templateParams' => [
                'data' => $programare->getData()->format('d-m-Y'),
                'ora' => $programare->getOra()->format('H:i'),
                'medic' => $programare->getPret()->getMedic()->getNume() . ' '
                    . $programare->getPret()->getMedic()->getPrenume(),
                'serviciu' => $programare->getPret()->getServiciu()->getDenumire(),
                'pret' => $programare->getPret()->getPret(),
                'programareId' => $programareId,
            ]
        ];

        $result = $emailService->sendEmail($emailData);

        return new JsonResponse(['message' => $result['message'], 'status' => $result['status']]);
    }
}