<?php

namespace App\Controller;

use App\Entity\Owner;
use App\Entity\Preturi;
use App\Entity\Servicii;
use App\Entity\User;
use App\Services\AdminService;
use App\Validator\TarifConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServiciiPreturiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private TarifConstraints $tarifConstraint,
        private TranslatorInterface $translator,
        private AdminService $adminService,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    #[Route("/servicii_preturi", name: "servicii_preturi")]
    public function preturi()
    {
        $medici = $this->em->getRepository(User::class)->getAllMedici();
        $servicii = $this->em->getRepository(Servicii::class)->getAllServicii();
        $owners = $this->em->getRepository(Owner::class)->getAllOwners();

        return $this->render('@templates/servicii_preturi.html.twig', [
            'medici' => $medici,
            'servicii' => $servicii,
            'owners' => $owners['owners']
        ]);
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
            ],
            ['propertyFilters' => [
                0 => ['preturi' => ['sters' => false]],
                1 => ['medic' => ['sters' => false]],
                2 => ['owner' => ['sters' => false]]]
            ]
        );

        $preturi = $this->em->getRepository(Preturi::class)->getAllPreturi($filter);

        return new JsonResponse([
            'data' => $preturi['servicii_preturi'],
            'recordsTotal' => intval($preturi['total']),
            'recordsFiltered' => intval($preturi['total'])
        ]);
    }

    #[Route("/sterge_pret", name: "sterge_pret", methods: ["POST"])]
    public function stergePret(Request $request) : Response
    {
        if (!$this->authorizationChecker->isGranted('DELETE', new Servicii())) {
            throw new AccessDeniedException();
        }

        $this->em->getRepository(Preturi::class)->deletePret($request->request->get('id'));

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("/add_serviciu", name: "add_serviciu", methods: ["POST"])]
    public function salveazaServiciu(Request $request) : Response
    {
        parse_str($request->request->get('form'), $formData);

        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new Servicii())) {
            throw new AccessDeniedException();
        }

        $this->em->getRepository(Servicii::class)->saveServiciu($formData);

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")
        ]);
    }

    #[Route("add_edit_pret", name: "add_edit_pret", methods: ["POST"])]
    public function salveazaPret(Request $request) : Response
    {
        parse_str($request->request->get('form'), $formData);

        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new Preturi())) {
            throw new AccessDeniedException();
        }

        $errors = $this->validator->validate($formData, $this->tarifConstraint);

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

        $this->em->getRepository(Preturi::class)->savePret($formData);

        return new JsonResponse(
            [
                "status" => Response::HTTP_OK,
                "message" => $this->translator->trans("Successful operation")
            ]
        );
    }

    #[Route('/pret', name: 'get_pret', methods: ['GET'])]
    public function getPret(Request $request) : Response
    {
        $pretData = $this->em->getRepository(Preturi::class)->getPret($request->query->get('id'));

        return new JsonResponse(
            [
                'pretData' => $pretData,
                'status' => Response::HTTP_OK,
                'message' => $this->translator->trans("Successful operation")
            ]
        );
    }

    #[Route('/get_preturi_medic', name: 'get_preturi_medic', methods: ['GET'])]
    public function getPreturiForMedic(Request $request): Response
    {
        $medic = $request->query->get('medic');

        $preturi = $this->em->getRepository(Preturi::class)->getPreturiMedic($medic);

        return new JsonResponse([
            'preturiMedic' => $preturi,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans("Successful operation")
        ]);
    }
}