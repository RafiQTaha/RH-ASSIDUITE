<?php

namespace App\Controller\Administrateur\Parametre;

use App\Entity\Users;
use App\Entity\UsModule;
use App\Entity\UsOperation;
use App\Entity\UsSousModule;
use App\Controller\ApiController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('administrateur/parametre/user')]
class UserController extends AbstractController
{
    private $em;
    private $api;
    public function __construct(ManagerRegistry $doctrine, ApiController $api)
    {
        $this->em = $doctrine->getManager();
        $this->api = $api;
    }
    #[Route('/', name: 'app_parametre_user')]
    public function index(Request $request): Response
    {
        $operations = $this->api->check($this->getUser(), 'app_parametre_user', $this->em, $request);
        // dd($operations);
        if (!is_array($operations)) {
            return $this->redirectToRoute('app_home');
        } elseif (count($operations) == 0) {
            return $this->render('includes/404.html.twig');
        }
        $modules = $this->em->getRepository(UsModule::class)->findAll();
        // $dossiers = $this->em->getRepository(PDossier::class)->findAll();

        return $this->render('administrateur/parametre/user/index.html.twig', [
            'operations' => $operations,
            'modules' => $modules,
            // 'dossiers' => $dossiers,
        ]);
    }
    #[Route('/app_parametre_user_list', name: 'app_parametre_user_list', options: ['expose' => true])]
    public function app_parametre_user_list(Request $request): Response
    {

        $draw = $request->query->get('draw');
        $start = $request->query->get('start') ?? 0;
        $length = $request->query->get('length') ?? 10;
        $search = $request->query->all('search')["value"];
        $orderColumnIndex = $request->query->all('order')[0]['column'];
        $orderColumn = $request->query->all("columns")[$orderColumnIndex]['name'];
        $orderDir = $request->query->all('order')[0]['dir'] ?? 'asc';
        // $operations = $this->api->check($this->getUser(), 'app_parametre_user', $this->em, $request);
        $admin_role = '["ROLE_ADMIN"]';
        $generateur_role = '["ROLE_GENERATEUR"]';

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('u.nom, u.id, u.prenom, u.email, u.roles, u.username, u.enable')
            ->from(Users::class, 'u')
            ->where("u.roles in ('$admin_role','$generateur_role') ");

        $queryBuilderRecords = $queryBuilder;

        if (!empty($search)) {
            $queryBuilder->andWhere('(u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR u.username LIKE :search)')
                ->setParameter('search', "%$search%");
        }

        if (!empty($orderColumn)) {
            $queryBuilder->orderBy("$orderColumn", $orderDir);
        }

        $filteredRecords = count($queryBuilder->getQuery()->getResult());
        // dump($filteredRecords);
        // Paginate results
        $queryBuilder->setFirstResult($start)
            ->setMaxResults($length);

        $results = $queryBuilder->getQuery()->getResult();
        // dd($results);
        foreach ($results as $key => $user) {
            $results[$key]['DT_RowId'] = $user['id'];
            // $results[$key]['actions'] = $this->renderView('administrateur/parametre/user/pages/access.html.twig', ['operations' => $operations, 'id' => $user['id']]);
            $results[$key]['roles'] = implode(",", $user['roles']);
        }
        $totalRecords = count($queryBuilderRecords->getQuery()->getResult());

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $results,
        ]);
    }
    #[Route('/app_parametre_user_activer', name: 'app_parametre_user_activer', options: ['expose' => true])]
    public function app_parametre_user_activer(Request $request): Response
    {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return new JsonResponse("vous n'êtes pas autorisé à faire cette action.", 500);
        }
        $usersIds = json_decode($request->get('usersIds'));
        foreach ($usersIds as $key => $usersId) {
            $user = $this->em->getRepository(Users::class)->find($usersId);
            $user->setEnable(true);
        }

        $this->em->flush();

        return new JsonResponse("Bien enregistrer");
    }
    #[Route('/app_parametre_user_desactiver', name: 'app_parametre_user_desactiver', options: ['expose' => true])]
    public function app_parametre_user_desactiver(Request $request): Response
    {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return new JsonResponse("vous n'êtes pas autorisé à faire cette action.", 500);
        }
        $usersId = json_decode($request->get('usersId'));
        $user = $this->em->getRepository(Users::class)->find($usersId);
        if ($user->isEnable() == true) {
            $user->setEnable(false);
        } else {
            $user->setEnable(true);
        }

        $this->em->flush();

        return new JsonResponse("Bien enregistrer");
    }

    #[Route('/getoperations/{user}', name: 'parametre_user_operations', options: ['expose' => true])]
    public function operations(Users $user): Response
    {
        $ids = [];
        foreach ($user->getOperations() as $operation) {
            array_push($ids, ["id" => $operation->getId()]);
        }
        return new JsonResponse($ids);
    }
    #[Route('/all/{user}/{type}', name: 'parametre_user_all', options: ['expose' => true])]
    public function all(Users $user, $type): Response
    {
        $operations = $this->em->getRepository(UsOperation::class)->findAll();
        if ($type === "add") {
            foreach ($operations as $operation) {
                $user->addOperation($operation);
            }
        } else if ($type === "remove") {
            foreach ($operations as $operation) {
                $user->removeOperation($operation);
            }
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/sousmodule/{sousModule}/{user}/{type}', name: 'parametre_user_sousmodule', options: ['expose' => true])]
    public function sousmodule(UsSousModule $sousModule, Users $user, $type): Response
    {
        if ($type === "add") {
            foreach ($sousModule->getOperations() as $operation) {
                $user->addOperation($operation);
            }
        } else if ($type === "remove") {
            foreach ($sousModule->getOperations() as $operation) {
                $user->removeOperation($operation);
            }
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/module/{module}/{user}/{type}', name: 'parametre_user_module', options: ['expose' => true])]
    public function module(UsModule $module, Users $user, $type): Response
    {
        if ($type === "add") {
            foreach ($module->getSousModules() as $sousModule) {
                foreach ($sousModule->getOperations() as $operation) {
                    $user->addOperation($operation);
                }
            }
        } else if ($type === "remove") {
            foreach ($module->getSousModules() as $sousModule) {
                foreach ($sousModule->getOperations() as $operation) {
                    $user->removeOperation($operation);
                }
            }
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }
    #[Route('/operation/{operation}/{user}/{type}', name: 'parametre_user_operation', options: ['expose' => true])]
    public function operation(UsOperation $operation, Users $user, $type): Response
    {
        if ($type === "add") {
            $user->addOperation($operation);
        } else if ($type === "remove") {
            $user->removeOperation($operation);
        } else {
            die("Veuillez contacter l'administrateur !");
        }
        $this->em->flush();
        return new JsonResponse(1);
    }

    #[Route('/new', name: 'parametre_user_register_new', options: ['expose' => true])]
    public function new(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->get('password') != $request->get('passwordc')) {
            return new JsonResponse('Les mots de passe ne correspondent pas', 500);
        }
        $user = $entityManager->getRepository(Users::class)->findOneBy(['username' => trim($request->get('username'))]);
        if ($user) {
            return new JsonResponse('Username déja exist', 500);
        }
        $user = new Users();
        $user->setUsername(trim($request->get('username')));
        $user->setEmail($request->get('email'));
        $user->setPrenom($request->get('prenom'));
        $user->setCreated(new \DateTime());
        $user->setNom($request->get('nom'));
        $user->setRoles(['ROLE_GENERATEUR']);
        $user->setPassword($userPasswordHasher->hashPassword(
            $user,
            $request->get('password')
        ));
        $user->setEnable(false);
        $entityManager->persist($user);
        $entityManager->flush();
        return new JsonResponse("Veuillez contacter l'administrateur pour active le compte!");
    }

    private UserPasswordHasherInterface $passwordEncoder;
    #[Route('/reinitialiser', name: 'parametre_user_reinitialiser', options: ['expose' => true])]
    public function reinitialiser(Request $request, UserPasswordHasherInterface $passwordHasher, UserPasswordHasherInterface $passwordEncoder)
    {
        $users = json_decode($request->get('usersId'));
        // dd($users);
        foreach ($users as $u) {
            $user = $this->em->getRepository(Users::class)->find($u);
            $this->passwordEncoder = $passwordEncoder;
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                '0123456789'
            ));
            $this->em->flush();
        }

        return new JsonResponse('Mot De Passe Bien Réinitialiser', 200);
    }
}
