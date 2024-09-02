<?php

namespace App\Controller;

use App\Entity\UsModule;
use App\Entity\UsSousModule;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Controller\ApiController;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class HomeController extends AbstractController
{
    private $em;
    private $api;

    public function __construct(ManagerRegistry $doctrine,ApiController $api)
    {
        $this->em = $doctrine->getManager();
        $this->api = $api;
    }


    #[Route('/', name: 'app_home', options: ['expose' => true])]
    public function home(Request $request, ManagerRegistry $doctrine): Response
    {
        // dd(in_array('ROLE_ADMIN', $this->getUser()->getRoles()));
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        } elseif (in_array('ROLE_ADMIN', $this->getUser()->getRoles()) || in_array('ROLE_USER', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_redirect');
        }

        return $this->render('includes/404.html.twig');
    }

    #[Route('/redirect', name: 'app_redirect', options: ['expose' => true])]

    public function redirectsite(Request $request): Response
    {
        $sousModules = $this->em->getRepository(UsSousModule::class)->findByUserOperations($this->getUser());
        $modules = $this->em->getRepository(UsModule::class)->getModuleBySousModule($sousModules);
        $data = [];
        foreach ($modules as $module) {
            $sousModuleArray = [];
            foreach ($sousModules as $sousModule) {
                if ($sousModule->getModule()->getId() == $module->getId()) {
                    array_push($sousModuleArray, $sousModule);
                }
            }
            array_push($data, [
                'module' => $module,
                'sousModule' => $sousModuleArray
            ]);
        }
        // dd($data);
        $request->getSession()->set('modules', $data);
        if (count($sousModules) < 1) {
            die("Vous n'avez aucun prévilege pour continue cette operation. veuillez contacter votre chef!");
        }
        $redirectToRoute =  $sousModules[0]->getlink();
        return $this->redirectToRoute($redirectToRoute);


        // return new JsonResponse(['redirect_url' => $redirectUrl]);


    }
}
