<?php

namespace App\Controller;

use App\Entity\AcAnnee;
use App\Entity\UsOperation;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\AcFormation;
use App\Entity\AcPromotion;
use App\Entity\AcSemestre;
use App\Entity\Dossier;
use App\Entity\Mouchard;
use App\Entity\PeriodeStage;
use App\Entity\Stage;
use App\Entity\TInscription;
;

#[Route('/api')]
class ApiController extends AbstractController
{
    private $em;
    private $api_univ;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        $this->api_univ = HttpClient::create();
    }

    #[Route('/formation/{etablissement}', name: 'getformation', options: ['expose' => true])]
    public function getformation($etablissement)
    {
        $formations = $this->em->getRepository(AcFormation::class)->findBy(['etablissement'=>$etablissement, 'active' => 1],['id'=>'ASC']);
        $data = self::dropdown($formations,'Formation');
        return new JsonResponse($data);
    }
    
    #[Route('/promotion/{formation}', name: 'getPromotion', options: ['expose' => true])]
    public function getPromotion(AcFormation $formation)
    {   
        $promotions = $this->em->getRepository(AcPromotion::class)->findBy(['formation'=>$formation, 'active' => 1],['id'=>'ASC']);
        $data = self::dropdown($promotions,'promotion');
        return new JsonResponse($data);
    }
    
    #[Route('/annee/{formation}', name: 'getAnnee', options: ['expose' => true])]
    public function getAnnee(AcFormation $formation)
    {   
        $annees = $this->em->getRepository(AcAnnee::class)->findBy(['formation'=>$formation, 'active' => 1],['designation'=>'DESC'],[1]);
        $data = self::dropdown($annees,'annee');
        return new JsonResponse($data);
    }
    
    // #[Route('/getanneebystage/{stage}', name: 'getAnneeByStage', options: ['expose' => true])]
    // public function getAnneeByStage(Stage $stage)
    // {   
    //     $formation = $stage->getSemestre()->getPromotion()->getFormation();
    //     $annees = $this->em->getRepository(AcAnnee::class)->findBy(['formation'=>$formation, 'active' => 1],['designation'=>'DESC']);
    //     $data = self::dropdown($annees,'annee');
    //     return new JsonResponse($data);
    // }
    
    #[Route('/semestre/{promotion}', name: 'getSemestre', options: ['expose' => true])]
    public function getSemestre($promotion)
    {   
        $semestre = $this->em->getRepository(AcSemestre::class)->findBy(['promotion'=>$promotion, 'active' => 1],['id'=>'ASC']);
        $data = self::dropdown($semestre,'Semestre');
        return new JsonResponse($data);
    }

    #[Route('/getStagesBySemestre/{semestre}', name: 'getStagesBySemestre', options: ['expose' => true])]
    public function getStagesBySemestre($semestre)
    {   
        $stages = $this->em->getRepository(Stage::class)->findBy(['semestre'=>$semestre, 'active' => 1],['designation'=>'ASC']);
        $data = self::dropdown($stages,'Stages');
        return new JsonResponse($data);
    }

    #[Route('/getperiodeByStage/{stage}/{annee}', name: 'getperiodeByStage', options: ['expose' => true])]
    public function getperiodeByStage($stage,$annee)
    {   
        // $periodes = $this->em->getRepository(PeriodeStage::class)->findBy(['stage'=>$stage, 'annee'=>$annee, 'fermer' => 0, 'active' => 1],['dateDebut'=>'ASC']);
        $periodes = $this->em->getRepository(PeriodeStage::class)->findActivePeriodeStageByStage($stage,$annee);
        
        $data = "<option selected value=''>Choix Periode</option>";
        foreach ($periodes as $periode) {
            $data .="<option value=".$periode->getId()." >".$periode->getDateDebut()->format('Y-m-d')." AU ".$periode->getDateFin()->format('Y-m-d')."</option>";
        }
        return new JsonResponse($data);
    }

    #[Route('/getNiveauByPromotion/{promotion}/{annee}', name: 'getNiveauByPromotion', options: ['expose' => true])]
    public function getNiveauByPromotion($promotion,$annee)
    {   
        // dd('test');
        // $niveaux = $this->em->getRepository(TInscription::class)->getNiveaux($promotion,$annee);
        $niveaux = $this->em->getRepository(TInscription::class)->getNiveauByPromoAnnee($promotion,$annee);
        // dd($niveaux);
        $data = "<option selected value=''>Choix Niveau</option>";
        foreach ($niveaux as $niveau) {
            $data .="<option value=".$niveau->getGroupe()->getId()." >".$niveau->getGroupe()->getNiveau()."</option>";
        }
        return new JsonResponse($data);
    }

    // public function FunctionName() : Returntype {
        
    // }
    // public function api_insert($table,$link)
    // {        
    //     dd('tessst');
    //     $newRow= 0;
    //     $from_id = 0;
    //     $responsepatients = $this->api_univ->request('GET',$this->getParameter('api_univ').$link.'/'.$from_id);
    //     $arraydata = $responsepatients->toArray();
    //     foreach ($arraydata as $key => $data) {
    //         $inserted = $this->makeInsertQuery($data,$table);
    //         if ($inserted) {
    //             $newRow++;
    //         }
    //     }
    //     // $stmt = $this->em->getConnection()->prepare($sqlRequest);
    //     // $stmt->executeQuery();
    //     return new JsonResponse(['code' => 200, 'newRow' => $newRow, 'updated' => 0],200);
    // }



    // function transformArray($inputArray,$table) {
    //     $outputArray = [];
    //     foreach ($inputArray as $key => $value) {
    //         // Extract the actual key without null bytes
    //         // dd($key);
    //         $cleanedKey = str_replace("\x00", '', $key);
    //         $cleanedKey = str_replace("App\Entity\\".$table, '', $cleanedKey);
    //         // Use the cleaned key in the new array
    //         // if ($cleanedKey != "acFormations") {
    //         //     dd($inputArray[$key],$value);
    //         //     # code...
    //         // }
    //         $outputArray[$cleanedKey] = $value;
    //     }
    //     return $outputArray;
    // }
    // #[Route('/api_getnaturesalarietype/{natureCab}', name: 'api_getnaturesalarietype', options: ['expose' => true])]
    // public function api_getnaturesalarietype(PNaturesalarieCab $natureCab)
    // {        
    //     $types = self::dropdown($natureCab->getDets(),'nature type');
    //     $contrats = self::dropdown($natureCab->getNature(),'nature contrat');
    //     $niveaux = $this->em->getRepository(PNiveau::class)->findAllNiveauByNatureCab($natureCab);
    //     $niveaux = self::dropdown($niveaux,'profile');

    //     return new JsonResponse(['types' => $types, 'contrats' => $contrats, 'niveaux' => $niveaux]);
    // }


    // static function dropdown_dure($objects,$choix)
    // {
    //     $data = "<option selected value=''>Choix ".$choix."</option>";
    //     foreach ($objects as $object) {
    //         $data .="<option value=".$object->getId()." >".$object->getNbrMois()." mois .</option>";
    //      }
    //      return $data;
    // }

    static function dropdown($objects,$choix)
    {
        $data = "<option selected value=''>Choix ".$choix."</option>";
        foreach ($objects as $object) {
            $data .="<option value=".$object->getId()." >".$object->getDesignation()."</option>";
         }
         return $data;
    }

    public function check($user, $link, $em, $request)
    {
        // dd($link);
        if ($request->getSession()->get("modules") == null) {
            return $this->redirectToRoute('app_home');
        }
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $operations = $em->getRepository(UsOperation::class)->findAllBySousModule($link);
            // dd($operations);
            return $operations;
        }
        // dd('taha');
        $operations = $em->getRepository(UsOperation::class)->getOperationByLinkSousModule($user, $link);
        return $operations;
    }

    public function ActiveStageByAdmission($user)
    {
        return $this->em->getRepository(PeriodeStage::class)->ActiveStageByAdmission($user);
    }
    
    public function getActiveDossiersByStageAndInscription($inscription,$periodeStage)
    {
        $dossiers = $this->em->getRepository(Dossier::class)->getActiveDossiersByStageAndInscription($inscription,$periodeStage);
        return $dossiers;
    }

    
    static function mouchard($user,$em,$object,$table,$action)
    {
        $entity = "App\Entity\\".$table;
        $array = (array) $object;
        foreach ($array as $key => $value) {
            if (!is_object($value)) {
                $nkey = str_replace($entity, '', $key) ;
                $nkey = preg_replace('/[\x00-\x1F\x7F]/u', '', $nkey);
                $array[$nkey] = $array[$key];
            }
            unset($array[$key]);
        }
        $mouchard = new Mouchard();
        $mouchard->setCreated(new \DateTime('now'));
        $mouchard->setUserCreated($user);
        $mouchard->setObservation($array);
        $mouchard->setFromTable($table);
        $mouchard->setAction($action);
        $em->persist($mouchard);
        $em->flush();
    }

}
