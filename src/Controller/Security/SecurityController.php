<?php

namespace App\Controller\Security;

use App\Entity\Users;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    private UserPasswordHasherInterface $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }
    #[Route(path: '/login', name: 'app_login', options: ['expose' => true])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_logout');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
    #[Route(path: '/app_changement_password', name: 'app_changement_password', options: ['expose' => true])]
    public function app_changement_password(Request $request, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $user = $em->getRepository(Users::class)->find($this->getUser()->getId());

        if(!$this->passwordEncoder->isPasswordValid($user, $request->get("mdp_current"))) {
            return new JsonResponse("Votre mot de passe actuel est incorrect !", 500);
        }
        // dd($request->get('mdp'), $request->get('mdp_confirmation'));
        if($request->get('mdp') === $request->get('mdp_confirmation')) {
            $user->setPassword($this->passwordEncoder->hashPassword(
                $user,
                $request->get('mdp_confirmation')
            ));
            
            $em->flush();
            return new JsonResponse("Bien Enregistre!", 200);
        }
        return new JsonResponse('Les mots de passe ne correspondent pas', 500);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
