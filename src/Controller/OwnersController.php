<?php

namespace App\Controller;

use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Services\AdminService;
use App\Validator\FirmeConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OwnersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private FirmeConstraints $constraint,
        private TranslatorInterface $translator,
        private AdminService $adminService,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    #[Route('/owners', name: 'owners', methods: ['GET'])]
    public function owners()
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new Owner())) {
            throw new AccessDeniedException();
        }

        return $this->render('@templates/owners.html.twig');
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

        $owners = $this->em->getRepository(Owner::class)->getAllOwners($filter);

        return new JsonResponse([
            'data' => $owners['owners'],
            'recordsTotal' => intval($owners['total']),
            'recordsFiltered' => intval($owners['total']),
        ]);
    }

    #[Route('add_edit_owner', name: 'add_edit_owner', methods: ['POST'])]
    public function salveazaOwner(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('ADD_EDIT', new Owner())) {
            throw new AccessDeniedException();
        }

        parse_str($request->request->get('form'), $formData);

        $errors = $this->validator->validate($formData, $this->constraint);

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

        $this->em->getRepository(Owner::class)->saveOwner($formData);

        return new JsonResponse(
            [
                'status' => Response::HTTP_OK,
                'message' => $this->translator->trans('Successful operation'),
            ]
        );
    }

    #[Route('get_owner', name: 'get_owner', methods: ['GET'])]
    public function getOwner(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new Owner())) {
            throw new AccessDeniedException();
        }

        $data = $this->em->getRepository(Owner::class)->getOwner($request->query->get('id'));

        return $this->render('@templates/firme/owner_content.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/sterge_owner', name: 'sterge_owner', methods: ['POST'])]
    public function sterge(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('DELETE', new Owner())) {
            throw new AccessDeniedException();
        }

        $id = $request->request->get('id');

        if ($this->em->getRepository(Consultatii::class)->ownerAreConsultatiiDeschise($id)) {
            return new JsonResponse([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $this->translator->trans('Owner has open consultations'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->getRepository(Owner::class)->deleteOwner($id);

        return new JsonResponse([
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans('Successful operation'),
        ]);
    }
}
