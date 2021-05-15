<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\EmptyBodyException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @var JWTTokenManagerInterface
     */
    private JWTTokenManagerInterface $tokenManager;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;

    /**
     * RegisterController constructor.
     *
     * @param ValidatorInterface $validator
     * @param JWTTokenManagerInterface $tokenManager
     * @param SerializerInterface $serializer
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        ValidatorInterface $validator,
        JWTTokenManagerInterface $tokenManager,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->validator = $validator;
        $this->tokenManager = $tokenManager;
        $this->serializer = $serializer;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function register(Request $request): JsonResponse
    {
        if (empty($request->getContent())) {
            throw new EmptyBodyException();
        }

        $suggestedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $context['groups'] = 'register';
        $errors = $this->validator->validate($suggestedUser, null, $context);
        if ($errors->count() > 0) {
            return $this->json($errors, 422);
        }
        $user = $this->userRepository->createUser($suggestedUser);
        $token = $this->tokenManager->create($user);
        return $this->json(['token' => $token]);
    }

    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $user->setPasswordChangedDate(time());
        $this->entityManager->flush();
        return $this->json(null, 204);
    }

    public function resetPassword(Request $request, int $userId): JsonResponse
    {
        if (empty($request->getContent())) {
            throw new EmptyBodyException();
        }

        $user = $this->userRepository->find($userId);
        if (is_null($user)) {
            throw new NotFoundHttpException('User not exist!');
        }

        /** @var User $suggestedUser */
        $suggestedUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        $user->setNewPassword($suggestedUser->getNewPassword());
        $user->setNewRetypedPassword($suggestedUser->getNewRetypedPassword());
        $user->setOldPassword($suggestedUser->getOldPassword());

        $context['groups'] = 'put-reset-password';
        $errors = $this->validator->validate($user, null, $context);
        if ($errors->count() > 0) {
            return $this->json($errors, 422);
        }
        $checkPass = $this->passwordEncoder->isPasswordValid($user, $suggestedUser->getOldPassword());
        if($checkPass !== true) {
            return $this->json(array('error' => 'The current password is incorrect.'), 422);
        }

        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getNewPassword()));
        $user->setPasswordChangedDate(time());

        $this->entityManager->flush();
        $token = $this->tokenManager->create($user);
        return $this->json(['token' => $token]);
    }
}
