<?php

namespace App\Controller\Assiduite\Pointage;

use App\Controller\ApiController;
use App\Entity\AcAnnee;
use App\Entity\AcSemestre;
use App\Entity\Checkinout;
use App\Entity\Employe;
use App\Entity\PeriodeStage;
use App\Entity\Stage;
use App\Entity\Userinfo;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('assiduite')]
class PointageController extends AbstractController
{
    private $em;
    private $api;
    private $emPointage;

    public function __construct(ManagerRegistry $doctrine, ApiController $api)
    {
        $this->em = $doctrine->getManager();
        $this->api = $api;
        $this->emPointage = $doctrine->getManager('pointage');
        date_default_timezone_set('Africa/casablanca');
    }
    
    #[Route('/pointage', name: 'app_pointage')]
    public function index(Request $request): Response
    {
        $operations = $this->api->check($this->getUser(), 'app_pointage', $this->em, $request);
        
        if (!is_array($operations)) {
            return $this->redirectToRoute('app_home');
        } elseif (count($operations) == 0) {
            return $this->render('includes/404.html.twig');
        }
        $lastdatesync = $this->em->getRepository(Checkinout::class)->findBy([],['checktime'=>'desc'],[1])[0]->getChecktime();
        // dd($lastdatesync);
        return $this->render('assiduite/pointage/index.html.twig', [
            'operations' => $operations,
            'lastdatesync'=> $lastdatesync
        ]);
    }
    
    #[Route('/app_pointage_list', name: 'app_pointage_list', options: ['expose' => true])]
    public function app_pointage_list(Request $request): Response
    {
        // dd($request->query->all('columns'),$request->query->all('columns')[0]['search']['value']);
        $draw = $request->query->get('draw');
        $start = $request->query->get('start') ?? 0;
        $length = $request->query->get('length') ?? 10;
        $search = $request->query->all('search')["value"];
        $orderColumnIndex = $request->query->all('order')[0]['column'];
        $columns =$request->query->all('columns');
        $orderColumn = $columns[$orderColumnIndex]['name'];
        $orderDir = $request->query->all('order')[0]['dir'] ?? 'asc';
        $filtre = " ";
        // dd($columns);
        if (!empty($columns[0]['search']['value'])) {
            $filtre .= " check.checktime like '" . $columns[0]['search']['value'] . "%' ";
        }else {
            $lastdatesync = $this->em->getRepository(Checkinout::class)->findBy([],['checktime'=>'desc'],[1])[0]->getChecktime()->format('Y-m-d');
            $filtre .= " check.checktime like '" . $lastdatesync . "%' ";
        }
        // dd($columns[0]['search']['value']);
        // dd($filtre);
        // if (!empty($columns[1]['search']['value'])) {
        //     $filtre .= " and frm.id = '" . $columns[1]['search']['value'] . "' ";
        // }

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('check.id, employe.id as id_employe, employe.nom as nom, employe.prenom as prenom, sit.designation as site,check.checktime as checktime,check.sn as sn,ctr.code as codeContract, ctr.fonction')
            ->from(Checkinout::class, 'check')
            ->innerJoin('check.userinfo', 'userinfo')
            ->innerJoin('userinfo.street', 'employe')
            ->innerJoin('employe.contracts', 'ctr')
            ->innerJoin('ctr.site', 'sit')
            ->where(" $filtre ")
            ;

        $queryBuilderRecords = $queryBuilder;
        
        if (!empty($search)) {
            $queryBuilder->andWhere('(employe.id LIKE :search OR employe.nom LIKE :search OR employe.prenom LIKE :search OR sit.designation LIKE :search OR check.userid LIKE :search OR ctr.fonction LIKE :search  )')
            ->setParameter('search', "%$search%");
        }
        if (!empty($orderColumn)) {
            // dd($orderColumn);
            $queryBuilder->orderBy("$orderColumn", $orderDir);
        }
        // dd($queryBuilder->getQuery());
        $filteredRecords = count($queryBuilder->getQuery()->getResult());
        
        // Paginate results
        $queryBuilder->setFirstResult($start)
            ->setMaxResults($length);

        $results = $queryBuilder->getQuery()->getResult();
        // dd($results);
        foreach ($results as $key => $result) {
            if ($result['checktime']) {
                $results[$key]['checktime'] = $result['checktime']->format('Y-m-d H:i:s');
            }
            // dd($results);
            $results[$key]['DT_RowId'] = $result['id'];
        }
        $totalRecords = count($queryBuilderRecords->getQuery()->getResult());
        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $results,
        ]);
    }

    
    #[Route('/synchro_pointage', name: 'synchro_pointage', options: ['expose' => true])]
    public function synchro_pointage(Request $request): Response
    {
        $date = $request->get('date');
        if ($date == "") {
            $date = date('Y-m-d');
        }
        
        $requete = "SELECT ch.* FROM `checkinout` ch
        inner join machines m on m.id = ch.machine_id
        where date(ch.checktime) = '$date' and m.type = 'EMP'";
        
        $stmt = $this->emPointage->getConnection()->prepare($requete);
        $newstmt = $stmt->executeQuery();   
        $pointages = $newstmt->fetchAll();
        $total = count($pointages);
        $count = 0;
        foreach ($pointages as $pointage) {
            $checktime = new DateTime($pointage['checktime']);
            $checkinout = $this->em->getRepository(Checkinout::class)->findOneBy([
                'sn'=>$pointage['sn'],
                'userid'=>$pointage['userid'],
                'checktime'=> $checktime
            ]);
            
            if ($checkinout) continue;
            $userinfo = $this->em->getRepository(Userinfo::class)->findOneBy(['Badgenumber'=>$pointage['userid']]);
            if (!$userinfo) continue;
            
            $checkinout = new Checkinout();
            $checkinout->setUserid($pointage['userid']);
            $checkinout->setsn($pointage['sn']);
            $checkinout->setChecktime($checktime);
            $checkinout->setMemoInfo($pointage['memoinfo']);
            $checkinout->setCreated(new DateTime('now'));
            $checkinout->setUserinfo($userinfo);
            $this->em->persist($checkinout);
            $this->em->flush();
            $count++;
        }
        
        return new JsonResponse($count. " Ajouté sur ". $total,200);
    }

    
    
    #[Route('/extraction_pointage', name: 'extraction_pointage', options: ['expose' => true])]
    public function extraction_pointage(Request $request)
    {   
        $spreadsheet = new Spreadsheet();
        $dateDebut = $request->get('dateDebut');
        $dateFin = $request->get('dateFin');
        // $dateFin = date('Y-m-d');
        // dd($dateDebut,$dateFin);
        $sheet = $spreadsheet->getActiveSheet();
        $i=2;
        $j=1;
        // $semaine_id = $this->em->getRepository(Semaine::class)->findSemaine(date('Y-m-d'))->getId();
        // $currentyear = date('m') > 7 ? $current_year = date('Y').'/'.date('Y')+1 : $current_year = date('Y') - 1 .'/' .date('Y');
        $pointages = $this->em->getRepository(Checkinout::class)->findCheckinoutByPeriode($dateDebut,$dateFin);
        // dd($pointages);
        if (!$pointages) {
            die('Pas de Poitage Pour Cette Periode.');
        }
        $sheet->fromArray(
            array_keys($pointages[0]),
            null,
            'A1'
        );
        foreach ($pointages as $key => $pointage) {
            $pointages[$key]['checktime'] = $pointage['checktime']->format('Y-m-d H:i:s');
            $sheet->fromArray(
                $pointage,
                null,
                'A'.$i
            );
            $i++;
            $j++;
        }
        $writer = new Xlsx($spreadsheet);
        // $currentyear = date('m') > 7 ? $current_year = date('Y').'-'.date('Y')+1 : $current_year = date('Y') - 1 .'-' .date('Y');
        $fileName = 'Extraction pointages De '.$dateDebut.' AU '.$dateFin.'.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    
    
    // #[Route('/infos_stage', name: 'infos_stage', options: ['expose' => true])]
    // public function infos_stage(Request $request): Response
    // {
    //     $stage = $this->em->getRepository(Stage::class)->find($request->get('stageId'));
    //     return new JsonResponse(['designation'=>$stage->getDesignation(),'abreviation'=>$stage->getAbreviation()],200);
    // }

    // #[Route('/modifier_stage', name: 'modifier_stage', options: ['expose' => true])]
    // public function modifier_stage(Request $request): Response
    // {
    //     // dd($request);
    //     $stage = $this->em->getRepository(Stage::class)->find($request->get('stageId'));
    //     if (!$stage) {
    //         return new JsonResponse('Stage Introuvable!!', 500);
    //     }
    //     if (!$request->get('designation')) {
    //         return new JsonResponse('Merci de remplir tout les champs!!', 500);
    //     }
    //     $stage->setDesignation($request->get('designation'));
    //     $stage->setAbreviation($request->get('abreviation'));
    //     $stage->setUpdated(new DateTime('now'));
    //     $stage->setUserUpdated($this->getUser());
    //     $this->em->flush();
    //     return new JsonResponse("Stage Bien Modifer!",200);
    // }

    
    // #[Route('/periode_stage', name: 'periode_stage', options: ['expose' => true])]
    // public function periode_stage(Request $request): Response
    // {
    //     // dd($request);
    //     $stage = $this->em->getRepository(Stage::class)->find($request->get('stageId'));
    //     if (!$stage) {
    //         return new JsonResponse('Stage Introuvable!!', 500);
    //     }
    //     if ($request->get('date_debut') == "" or $request->get('date_fin') == "" or $request->get('annee') == "") {
    //         return new JsonResponse('Merci de remplir tout les champs!!', 500);
    //     }
    //     $annee = $this->em->getRepository(AcAnnee::class)->find($request->get('annee'));
    //     if (!$annee) {
    //         return new JsonResponse('Annee Introuvable!!', 500);
    //     }
    //     $dateDebut = new datetime($request->get('date_debut'));
    //     $dateFin = new datetime($request->get('date_fin'));
    //     if ($dateDebut >= $dateFin) {
    //         return new JsonResponse('La date Fin doit etre superieur au Date Debut!!', 500);
    //     }
    //     $periodeStage = new PeriodeStage();
    //     $periodeStage->setAnnee($annee);
    //     $periodeStage->setStage($stage);
    //     $periodeStage->setDateDebut($dateDebut);
    //     $periodeStage->setDateFin($dateFin);
    //     $periodeStage->setCreated(new DateTime('now'));
    //     $periodeStage->setUserCreated($this->getUser());
    //     $this->em->persist($periodeStage);
    //     $this->em->flush();
    //     return new JsonResponse("Periode Bien Ajouter!",200);
    // }

    // #[Route('/supprimer_stage', name: 'supprimer_stage', options: ['expose' => true])]
    // public function supprimer_stage(Request $request): Response
    // {
    //     $stage = $this->em->getRepository(Stage::class)->find($request->get('stageId'));
    //     if (!$stage) {
    //         return new JsonResponse('Stage Introuvable!!', 500);
    //     }
    //     if (count($stage->getPeriodeStages())) {
    //         return new JsonResponse('Ce Stage a déja des periode affecté.. merci de les annulées!!', 500);
    //     }
    //     $stage->setActive(0);
    //     $this->em->flush();
    //     return new JsonResponse("Stage Bien Supprimer!",200);
    // }
}
