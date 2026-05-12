<?php

namespace App\Controller;

use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Entity\Preturi;
use App\Entity\RapoarteColaboratori;
use App\Entity\User;
use App\PDF\Service\PdfService;
use App\Services\NomenclatoareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class RapoarteController extends AbstractController
{
    const NEPLATIT = 'neplatita';
    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    #[Route("/rapoarte", name: "rapoarte", methods: ["GET"])]
    public function rapoarte(NomenclatoareService $service)
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new RapoarteColaboratori())) {
            throw new AccessDeniedException();
        }

        $medici = $this->em->getRepository(User::class)->getAllMedici();
        $owners = $this->em->getRepository(Owner::class)->getAllOwners();
        $lunileAnului = $service->getLunileAnului();

        return $this->render('rapoarte.html.twig', [
            'medici' => $medici,
            'owners' => $owners['owners'],
            'lunileAnului' => $lunileAnului
        ]);
    }

    #[Route("/list_rapoarte_colaboratori", name: "list_rapoarte_colaboratori", methods: ["GET"])]
    public function listRapoarteColaboratori(Request $request) : Response
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new RapoarteColaboratori())) {
            throw new AccessDeniedException();
        }

        $filter['propertyFilters'] = [];

        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => ($request->query->all('order'))[0] ?? null,
                'start' => $request->query->get('start'),
                'length' => $request->query->get('length')
            ]
        );

        if (in_array($this->getUser()->getRole()->getDenumire(), ['ROLE_Medic', 'ROLE_Psiholog'])) {
            $filter['propertyFilters'][] = ['rapoarte_colaboratori' => ['medic' => $this->getUser()->getId()]];
        }

        $rapoarteColaboratori = $this->em->getRepository(RapoarteColaboratori::class)
            ->getAllRapoarteColaboratori($filter);

        return new JsonResponse([
            'data' => $rapoarteColaboratori['rapoarteColaboratori'],
            'recordsTotal' => intval($rapoarteColaboratori['total']),
            'recordsFiltered' => intval($rapoarteColaboratori['total'])
        ]);
    }

    #[Route("/add_raport_colaboratori", name: "add_raport_colaboratori", methods: ["POST"])]
    public function saveRapoarteColaboratori(Request $request) : Response
    {
        parse_str($request->request->get('formData'), $formData);

        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new RapoarteColaboratori())) {
            throw new AccessDeniedException();
        }

        $raportId = $this->em->getRepository(RapoarteColaboratori::class)->saveRaportColaboratori($formData);

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation"),
            "id" => $raportId ?? null
        ]);
    }

    #[Route("/calculeaza_plata_colaborator", name: "calculeaza_plata_colaborator", methods: ["GET"])]
    public function calculeazaPlataColaborator(Request $request) : Response
    {
        parse_str($request->query->get('formData'), $formData);

        $raport = $this->em->getRepository(RapoarteColaboratori::class)->getRaportByFilters($formData);

        $totalDePlata = $this->em->getRepository(Consultatii::class)->calculeazaPlataColaborator($formData);

        if (!$raport && $totalDePlata) {
            return new JsonResponse([
                "stare" => "nou",
                "totalDePlata" => $totalDePlata,
                "status" => Response::HTTP_OK,
                "message" => $this->translator->trans("Successful operation")
            ]);
        }

        if (self::NEPLATIT === $raport['stare']) {
            return new JsonResponse([
                "id" => $raport['id'],
                "stare" => "neplatit",
                "totalDePlata" => $totalDePlata,
                "status" => Response::HTTP_OK,
                "message" => $this->translator->trans("It can be paid")
            ]);
        }

        $message = $raport['id']
            ? $this->translator->trans("There is a report for the selected data")
            : $this->translator->trans("No amounts to pay");

        return new JsonResponse([
            "id" => $raport['id'],
            "stare" => $raport['stare'],
            "totalDePlata" => $totalDePlata,
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans($message)
        ]);
    }

    #[Route("/plateste_colaborator", name: "plateste_colaborator", methods: ["POST"])]
    public function platesteColaborator(Request $request) : Response
    {
        parse_str($request->request->get('formData'), $formData);

        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new RapoarteColaboratori())) {
            throw new AccessDeniedException();
        }

        $this->em->getRepository(RapoarteColaboratori::class)->platesteColaborator($formData);

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/pdf_plata_colaborator", name: "pdf_plata_colaborator", methods: ["POST"])]
    public function pdf(Request $request, PdfService $pdfService): Response
    {
        $pdfService->printToPdf(
            $request->request->get('id'),
            'plata_colaborator.html.twig',
            [
                'orientation' => 'L',
                'footer' => '{PAGENO}/{nb}'
            ]
        );

        return new JsonResponse([
            Response::HTTP_OK,
            $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/plata_colaborator", name: "plata_colaborator", methods: ["POST"])]
    public function plataColaborator(Request $request) : Response
    {
        parse_str($request->request->get('formData'), $formData);

        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new RapoarteColaboratori())) {
            throw new AccessDeniedException();
        }

        $formData['raport_colaboratori_id'] = $formData['raport_plateste_id'];

        $this->em->getRepository(RapoarteColaboratori::class)->platesteColaborator($formData);

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/get_colaboratori_owner", name: "get_colaboratori_owner", methods: ["GET"])]
    public function getColaboratoriOwner(Request $request) : Response
    {
        $ownerId = $request->query->get('ownerId');

        $data = $this->em->getRepository(Preturi::class)->getMediciForOwner($ownerId);

        return new JsonResponse([
            'data' => $data,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Data collection success")
        ]);
    }
}