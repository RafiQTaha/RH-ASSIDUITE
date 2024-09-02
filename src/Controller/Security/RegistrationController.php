<?php

namespace App\Controller\Security;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('security/register.html.twig', [
        ]);
    }
    #[Route('/new', name: 'app_register_new', options: ['expose' => true])]
    public function new(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        if($request->get('password') != $request->get('passwordc')) {
            return new JsonResponse('Les mots de passe ne correspondent pas', 500);
        }
        $user = $entityManager->getRepository(Users::class)->findOneBy(['username' => trim($request->get('username'))]);
        if($user) {
            return new JsonResponse('Username déja exist', 500);
        }
        $user = new Users();
        $user->setUsername(trim($request->get('username')));
        $user->setEmail($request->get('email'));
        $user->setPrenom($request->get('prenom'));
        $user->setCreated(new \DateTime());
        $user->setNom($request->get('nom'));
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($userPasswordHasher->hashPassword(
            $user,
            $request->get('password')
        ));
        $user->isEnable(false);
        $entityManager->persist($user);
        $entityManager->flush();
        return new JsonResponse("Veuillez contacter l'administrateur pour active votre compte!");
    }
}
