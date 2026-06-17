<?php

namespace App\Controller;

use App\Entity\Consultatie;
use App\Entity\Owner;
use App\Entity\Pacient;
use App\Entity\Pret;
use App\Entity\Serviciu;
use App\Entity\User;
use App\Form\PacientFormType;
use App\Services\NomenclatoareService;
use App\Services\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PacientController extends AbstractController
{
    const NEINCASATA = 0;

    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private AuthorizationCheckerInterface $authorizationChecker,
        private FormFactoryInterface $formFactory
    ) {}

    #[Route('/pacienti', name: 'pacienti', methods: ['GET'])]
    public function pacienti()
    {
        return $this->render('@templates/pacienti.html.twig');
    }

    #[Route("/list", name: "list", methods: ["GET"])]
    public function list(Request $request) : Response
    {
        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => ($request->query->all('order'))[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length')
            ]
        );

        $pacienti = $this->em->getRepository(Pacient::class)->getAllPacienti($filter);

        return new JsonResponse([
            'data' => $pacienti['pacienti'],
            'recordsTotal' => intval($pacienti['total']),
            'recordsFiltered' => intval($pacienti['total'])
        ]);
    }

    #[Route("/list_pacienti_cu_consultatii", name: "list_pacienti_cu_consultatii", methods: ["GET"])]
    public function listPacientiCuConsultatii(Request $request) : Response
    {
        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => ($request->query->all('order'))[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length')
            ]
        );

        $pacienti = $this->em->getRepository(Pacient::class)->getAllPacientiCuConsultatii($filter);

        return new JsonResponse([
            'data' => $pacienti['pacienti'],
            'recordsTotal' => intval($pacienti['total']),
            'recordsFiltered' => intval($pacienti['total'])
        ]);
    }
    #[Route("/list_pacienti_in_cabinet", name: "list_pacienti_in_cabinet", methods: ["GET"])]
    public function listPacientiInCabinet(Request $request) : Response
    {
        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => ($request->query->all('order'))[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length')
            ]
        );

        $pacienti = $this->em->getRepository(Pacient::class)->getPacientiInCabinet($filter);

        return new JsonResponse([
            'data' => $pacienti['pacienti'],
            'recordsTotal' => intval($pacienti['total']),
            'recordsFiltered' => intval($pacienti['total'])
        ]);
    }

    #[Route("/sterge_pacient", name: "sterge_pacient", methods: ["POST"])]
    public function sterge(Request $request) : Response
    {
        if (!$this->authorizationChecker->isGranted('DELETE', new Pacient())) {
            throw new AccessDeniedException();
        }

        $pacient = $this->em->getRepository(Pacient::class)->find($request->request->get('id'));

        if (!$pacient->getConsultatii()->isEmpty() || !$pacient->getProgramari()->isEmpty()) {
            return new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $this->translator->trans('Patient has consultations or appointments.')
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->getRepository(Pacient::class)->deletePacient($request->request->get('id'));

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/add_edit_pacient", name: "add_edit_pacient", methods: ["POST"])]
    public function salveazaPacient(Request $request, NomenclatoareService $service) : JsonResponse
    {
        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new Pacient())) {
            throw new AccessDeniedException();
        }

        $pacient = $this->getPacientForRequest($request);
        $form = $this->createPacientForm($pacient, $service);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(
                [
                    'status'  => Response::HTTP_BAD_REQUEST,
                    'message' => $this->translator->trans("Failed operation") . ' ' .
                        $this->buildFormValidationErrors($form->getErrors(true)),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->em->getRepository(Pacient::class)->savePacient($pacient, $this->getUser());

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("get_pacient", name: "get_pacient", methods: ["GET"])]
    public function getPacient(Request $request, NomenclatoareService $service) : Response
    {
        $data = $this->em->getRepository(Pacient::class)->getPacient($request->query->get('id'));
        $pacient = $this->getPacientForData($data);
        $form = $this->createPacientForm($pacient, $service, $data['varsta'] ?? '', $data['id'] ?? '');

        $servicii = $this->em->getRepository(Serviciu::class)->getAllServicii();
        $medici = $this->em->getRepository(User::class)->getAllMedici();
        $owners = $this->em->getRepository(Owner::class)->getAllOwners();

        return $this->render('@templates/modals/add_edit_pacient_content.html.twig', [
            'form' => $form->createView(),
            'servicii' => $servicii,
            'medici' => $medici,
            'owners' => $owners
        ]);
    }

    #[Route("/get_preturi", name: "get_preturi", methods: ["GET"])]
    public function getPreturi(Request $request) : Response
    {
        $filter = [
            'value' => $request->query->get('filter'),
            'propertyFilters' => [],
        ];

        $preturi = $this->em->getRepository(Pret::class)->getAllPreturi($filter);
        $serviciiPacientInCabinet = $this->em->getRepository(Consultatie::class)->getServiciiPacient([
                'pacientId' => $request->query->get('pacientId'),
                'dataPrezentare' => $request->query->get('dataPrezentare'),
                'incasata' => self::NEINCASATA
            ]);

        $serviciiIds = array_column($serviciiPacientInCabinet, 'pretId');

        return new JsonResponse([
            'preturi' => $preturi,
            'serviciiPacientInCabinet' => $serviciiIds,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/deschide_sterge_consultatii", name: "deschide_sterge_consultatii", methods: ["POST"])]
    public function deschideStergeConsultatii(Request $request, PushNotificationService $service) : Response
    {
        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new Consultatie())) {
            throw new AccessDeniedException();
        }

        $pacientId = $request->request->get('pacientId');
        $programareId = $request->request->get('programareId');
        $dataPrezentare = $request->request->get('dataPrezentare');
        $serviciiRequest = $request->request->all('serviciiPreturiIds');
        $serviciiPreturi = isset($serviciiRequest) ? $serviciiRequest : [];

        $ids = $this->em->getRepository(Consultatie::class)
            ->deschideStergeConsultatii($pacientId, $programareId, $serviciiPreturi, $dataPrezentare);

        $service->pushNotificationToMercure('deschide_sterge_consultatii');

        return new JsonResponse([
            'consultatiiIds' => $ids,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/get_servicii_pacient", name: "get_servicii_pacient", methods: ["GET"])]
    public function getServiciiPacient(Request $request) : Response
    {
        $filter = [
            'pacientId' => $request->query->get('pacient_id'),
            'inchisa' =>  $request->query->get('inchisa'),
            'incasata' =>  $request->query->get('incasata'),
            'dataPrezentare' =>  $request->query->get('dataPrezentare')
        ];

        $serviciiPacient = $this->em->getRepository(Consultatie::class)->getServiciiPacient($filter);

        return new JsonResponse([
            'serviciiPacient' => $serviciiPacient,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/incaseaza_consultatii", name: "incaseaza_consultatii", methods: ["POST"])]
    public function incaseazaConsultatii(Request $request) : Response
    {
        $consultatii = $request->request->all('consultatii');

        $this->em->getRepository(Consultatie::class)->incaseazaConsultatii($consultatii);

        return new JsonResponse([
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/inchide_toate", name: "inchide_toate", methods: ["POST"])]
    public function inchideToateConsInvPacient(Request $request) : Response
    {
        $pacientId = $request->request->get('id');

        if (!$this->authorizationChecker->isGranted('CLOSE_ALL', new Consultatie())) {
            throw new AccessDeniedException();
        }

        $this->em->getRepository(Consultatie::class)->inchideToateConsInvPacient($pacientId);

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/verifica_unicitate_cnp", name: "verifica_unicitate_cnp", methods: ["POST"])]
    public function verificaUnicitateCnp(Request $request) : Response
    {
        $cnpRequest = $request->request->get('cnp');

        $cnp = $this->em->getRepository(Pacient::class)->findOneBy(['cnp' => $cnpRequest]);

        if ($cnp !== null) {
            throw new BadRequestHttpException($this->translator->trans("Exists") . " CNP / ID",
                null, 4009);
        }

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/get_pacienti_by_cnp", name: "get_pacienti_by_cnp", methods: ["GET"])]
    public function getPacientiByCnp(Request $request) : Response
    {
        $cnpRequest = $request->query->get('cnp');

        $pacienti = $this->em->getRepository(Pacient::class)->getPacientiByCnp($cnpRequest);

        return new JsonResponse([
            'pacienti' => $pacienti,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }

    private function getPacientForRequest(Request $request): Pacient
    {
        $pacientId = $request->request->get('pacient_id') ?: $request->request->get('id');

        if (!$pacientId) {
            return $this->getInitializedPacient();
        }

        $pacient = $this->em->getRepository(Pacient::class)->find($pacientId);

        if (!$pacient instanceof Pacient) {
            throw new BadRequestHttpException('Missing ID');
        }

        return $pacient;
    }

    private function getPacientForData(array $data): Pacient
    {
        $pacient = $this->getInitializedPacient();

        if (!$data) {
            return $pacient;
        }

        $pacient->setNume($data['nume']);
        $pacient->setPrenume($data['prenume']);
        $pacient->setCnp($data['cnp']);
        $pacient->setTelefon($data['telefon']);
        $pacient->setAdresa($data['adresa']);
        $pacient->setTara($data['tara']);
        $pacient->setCi($data['ci'] ?? null);
        $pacient->setCiEliberat($data['ciEliberat'] ?? null);
        $pacient->setJudet($data['judet'] ?? null);
        $pacient->setLocalitate($data['localitate'] ?? null);
        $pacient->setTelefon2($data['telefon2'] ?? null);
        $pacient->setEmail($data['email'] ?? null);
        $pacient->setOcupatie($data['ocupatie'] ?? null);
        $pacient->setLocMunca($data['locMunca'] ?? null);
        $pacient->setStareCivila($data['stareCivila'] ?? 0);
        $pacient->setObservatii($data['observatii'] ?? null);

        return $pacient;
    }

    private function getInitializedPacient(): Pacient
    {
        $pacient = new Pacient();
        $pacient->setNume('');
        $pacient->setPrenume('');
        $pacient->setCnp('');
        $pacient->setTelefon('');
        $pacient->setAdresa('');
        $pacient->setTara('Romania');
        $pacient->setLocalitate('');
        $pacient->setStareCivila(0);

        return $pacient;
    }

    private function createPacientForm(
        Pacient $pacient,
        NomenclatoareService $service,
        string|int|null $varsta = '',
        string|int|null $pacientId = null
    ) {
        return $this->formFactory->createNamed('', PacientFormType::class, $pacient, [
            'tari' => $service->getTari(),
            'judete' => $service->getJudete(),
            'stariCivile' => $service->getStariCivile(),
            'varsta' => $varsta,
            'pacient_id' => $pacientId ?? $pacient->getId(),
        ]);
    }

    private function buildFormValidationErrors(FormErrorIterator $errors): string
    {
        $messages = '';

        foreach ($errors as $error) {
            $messages .= ' ' . $this->translator->trans($error->getMessage());
        }

        return $messages;
    }
}
