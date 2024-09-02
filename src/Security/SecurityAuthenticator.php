<?php

namespace App\Security;

use App\Controller\ApiController;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class SecurityAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;
    
    private $em;
    private $api;
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(UrlGeneratorInterface $urlGenerator,UserPasswordHasherInterface $passwordEncoder, UsersRepository $userRepository, ManagerRegistry $doctrine,ApiController $api)
    {
        $this->urlGenerator = $urlGenerator;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->doctrine = $doctrine;
        $this->em = $doctrine->getManager();
        $this->api = $api;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username, function($userIdentifier) {
                // optionally pass a callback to load the User manually
                $user = $this->userRepository->findOneBy(['username' => $userIdentifier]);
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException("Username introuvable!");
                }
                return $user;
            }),
            new CustomCredentials(function($credentials, Users $user) {
                if(!$this->passwordEncoder->isPasswordValid($user, $credentials)) {
                    throw new CustomUserMessageAuthenticationException("Votre mot de passe est incorrect!");
                }
                if(!$user->isEnable()) {
                    throw new CustomUserMessageAuthenticationException("Votre compte est desactiver veuillez contacte l'adminsitrateur");
                }
                if(in_array('ROLE_ETUDIANT', $user->getRoles())){
                    $stage = $this->api->ActiveStageByAdmission($user->getUsername());
                    if (!$stage) {
                        throw new CustomUserMessageAuthenticationException("Vous n'avez aucun stage pour le mement!");
                    }
                    
                }
                return true;
            }, $password),
                [
                    new RememberMeBadge(),
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
