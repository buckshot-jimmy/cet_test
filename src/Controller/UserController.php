<?php

namespace App\Controller;

use App\Entity\Consultatii;
use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use App\Services\AdminService;
use App\Validator\UserConstraints;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private AdminService $adminService,
        private ValidatorInterface $validator,
        private UserConstraints $userConstraint,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    #[Route("/user", name: "user", methods: ["GET"])]
    public function utilizatori()
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new User())) {
            throw new AccessDeniedException();
        }

        return $this->render('utilizatori.html.twig');
    }

    #[Route("/list", name: "list", methods: ["GET"])]
    public function list(Request $request) : Response
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new User())) {
            throw new AccessDeniedException();
        }

        $filter = array_merge(
            $request->query->all('search'),
            [
                'sort' => ($request->query->all('order'))[0] ?? null,
                'loggedUserId' => $this->getUser()->getId(),
            ]
        );

        $utilizatori = $this->em->getRepository(User::class)->getAllUsers($filter);

        return new JsonResponse([
            'data' => $utilizatori['utilizatori'],
            'recordsTotal' => intval($utilizatori['total']),
            'recordsFiltered' => intval($utilizatori['total'])
        ]);
    }

    #[Route("/sterge", name: "sterge", methods: ["POST"])]
    public function sterge(Request $request) : Response
    {
        if (!$this->authorizationChecker->isGranted('DELETE', new User())) {
            throw new AccessDeniedException();
        }

        $this->em->getRepository(User::class)->deleteUser($request->request->get('id'));

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans("Successful operation")]
        );
    }

    #[Route("/add_edit_user", name: "add_edit_user", methods: ["POST"])]
    public function salveazaUtilizator(
        Request $request,
        UserAuthenticatorInterface $uai,
        LoginFormAuthenticator $lfa) : Response
    {
        parse_str($request->request->get('form'), $formData);

        $attribute = 'ADD';
        $subject = new User();

        if ($formData['editUserId']) {
            $attribute = 'EDIT';
            $subject = $this->em->getRepository(User::class)->findOneBy(['id' => $formData['editUserId']]);
            $formData['role_name'] = $subject->getRole()->getDenumire();
        }

        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException();
        }

        $errors = $this->validator->validate($formData, $this->userConstraint);

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

        $user = $this->em->getRepository(User::class)->saveUser($formData);

        if ($formData['editUserId'] === $formData['loggedUserId']) {
            $uai->authenticateUser($user, $lfa, $request);
        }

        return new JsonResponse([
            "status" => Response::HTTP_OK,
            "message" => $this->translator->trans('Successful operation')]
        );
    }

    #[Route("/get_user", name: "get_user", methods: ["GET"])]
    public function getUtilizator(Request $request) : Response
    {
        $userData = $this->em->getRepository(User::class)->getUser($request->query->get('id'));

        return new JsonResponse([
            'userData' => $userData,
            'status' => Response::HTTP_OK,
            'message' => $this->translator->trans('Successful operation')]
        );
    }
}
