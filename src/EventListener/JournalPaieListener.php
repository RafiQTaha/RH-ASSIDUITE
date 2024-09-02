<?php 
namespace App\EventListener;

use App\Entity\JournalBulletinLg;
use App\Entity\JournalPaieDossier;
use App\Entity\PBordereau;
use App\Entity\PCompteComptable;
use App\Entity\Prubrique;
use App\Entity\PStatut;
use App\Entity\TbulletinLg;
use App\Entity\VMatrix;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class JournalPaieListener {
    private $entityManager;
    private $charge;


    public function __construct(EntityManagerInterface  $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        $request = $event->getRequest();
     
        if ($request->get('_route') === 'app_bulletin_employe_calcul' or 
            $request->get('_route') === 'app_bulletin_employe_calcul_all' or
            $request->get('_route') === 'app_paie_indeminite_insert' or
            $request->get('_route') === 'app_paie_honoraire_insert' or
            $request->get('_route') === 'app_tresorerie_bordereau_journal') 
        {
            if($request->request->get('bordoreauIds')) {
                $bordoreauIds = json_decode($request->request->get('bordoreauIds'));
              
                foreach ($bordoreauIds as $key => $bordoreauId) {
                    $bordoreau = $this->entityManager->getRepository(PBordereau::class)->find($bordoreauId);                    
                    $this->charge = 0;
                    $this->entityManager->refresh($bordoreau);
                    foreach ($bordoreau->getActiveBulletins() as $key => $bulletin) {
                        $this->journalBulletin($bulletin);
                    }
                    if(number_format($this->charge, 2) == 0) {
                        $bordoreau->setStatut(
                            $this->entityManager->getRepository(PStatut::class)->find(2)
                        );
                    } else {
                        $bordoreau->setStatut(
                            $this->entityManager->getRepository(PStatut::class)->find(5)
                        );
                    }
                    
                }
                $this->entityManager->flush();
            }
        }

        return;
    }


    public function journalBulletin($bulletin) {
        $cotisationsArray = [
            "cnss" => [50, 47, 53],
            "cimr" => [48],
            "ir" => [43],
            // "netpaye" => "K10010','K10011"
        ];
        $bordoreau = $bulletin->getBordereau();
        $primes = $this->entityManager->getRepository(Prubrique::class)->findBy(['id' => [4,6,7,8,9,10,11,16,24,25,60,76]]);
        if($bordoreau->getNatureContract()->getType()->getId() == 2) {
            $primes = [];
        } else {
            $primes = $this->entityManager->getRepository(TbulletinLg::class)->findBy(['bulletin' => $bulletin, 'active' => true, 'rubrique' => $primes]);
        }
        
        if($bulletin->getDossier()->getGroupement() == 'FCZ') {
            $prevelements = $this->entityManager->getRepository(Prubrique::class)->findBy(['id' => [5,68,30,31,33,34,35,36,37,38,39,85,80,79,78,77,82,83,45,84]]);
            $prevelementCabs = $this->entityManager->getRepository(Prubrique::class)->findBy(['id' => [29,32,40,41,44,46,59]]); // if u change this ids please change also in TbulletinLgRepositry for regularisation
            $prevelements = $this->entityManager->getRepository(TbulletinLg::class)->findByGroup($bulletin,  $prevelements);
            $prevelementCabs = $this->entityManager->getRepository(TbulletinLg::class)->findByGroup($bulletin, $prevelementCabs);
            
        } else {
            $prevelements = $this->entityManager->getRepository(Prubrique::class)->findBy(['id' => [5,68,30,31,33,34,35,36,37,38,39,85,80,79,78,77,82,83,45,84, 29,32,40,41,44,46,59]]);
            $prevelements = $this->entityManager->getRepository(TbulletinLg::class)->findByGroup($bulletin, $prevelements);
            $prevelementCabs = [];
        }


        // disable if exist already to insert new lines
        // $journalBulletinLgs = $bulletin->getJournalBulletinLgs();
        $class = JournalBulletinLg::class;
        $query = $this->entityManager->createQuery(
            "UPDATE $class e SET e.active = :newValue WHERE e.bulletin = :someValue and e.active = 1"
        );
        $query->setParameters([
            'newValue' => '0',
            'someValue' => $bulletin,
        ]);
        $query->execute();
        // foreach($journalBulletinLgs as $journalBulletinLg) {
        //     $journalBulletinLg->setActive(false);
        // }


        if ($bordoreau->getNatureContract()->getType()->getId() == 1 && $bordoreau->getType() == 'paie') {
            $salaireBase = $this->entityManager->getRepository(TbulletinLg::class)->findOneBy(['bulletin' => $bulletin, 'rubrique' => $this->entityManager->getRepository(Prubrique::class)->find(1), 'active' => true]);
            $salaireAnciennte = $this->entityManager->getRepository(TbulletinLg::class)->findOneBy(['bulletin' => $bulletin, 'rubrique' => $this->entityManager->getRepository(Prubrique::class)->find(2), 'active' => true]);
            
            $montantSalaireBase = $salaireBase->getMontant();
            $montantSalaireBaseInitial = $salaireBase->getMontant();
            
            $montantSalaireAnciennte = $salaireAnciennte ? $salaireAnciennte->getMontant() : 0;
            $montantSalaireAnciennteInitital = $salaireAnciennte ? $salaireAnciennte->getMontant() : 0;
        } else {
            $rubriques = $this->entityManager->getRepository(Prubrique::class)->findBy(['id' => [3,12,13, 14,15,17,18,22,26,27,61,63,64,65,66, 67,69,70]]);
            $salaireBase = $this->entityManager->getRepository(TbulletinLg::class)->findOneBy(['bulletin' => $bulletin, 'rubrique' => $rubriques, 'active' => true]);
            
            $montantSalaireBase = $salaireBase->getMontant();
            $montantSalaireBaseDevise = $salaireBase->getMontantDevise() ?? 0;
            $montantSalaireBaseInitial = $salaireBase->getMontant();
            $montantSalaireAnciennte = 0;
            $montantSalaireAnciennteInitital = 0;
        }

        if($montantSalaireBase == 0) {
            return;
        }

        foreach ($cotisationsArray as $key => $cotisationArray) {
            $cotisations = $this->entityManager->getRepository(TbulletinLg::class)->findByCotisation($bulletin, $cotisationArray);
            $montantTotalCotisations = $this->entityManager->getRepository(TbulletinLg::class)->findByCotisationGroupBulletin($bulletin, $cotisationArray);
            if($cotisations and $montantTotalCotisations){

                $montantInitial = round($montantTotalCotisations['montant'], 2);

                $halfMontant = round(($montantTotalCotisations['montant'] / 2), 2);
                if($halfMontant + $halfMontant != $montantInitial) {
                    $firstHalf = $halfMontant;
                    $secondHalf = $montantInitial - $halfMontant;
                } else {
                    $firstHalf = $secondHalf = $halfMontant;
                }
                if ($firstHalf > $montantSalaireAnciennte) {
                    $minus = round(($firstHalf - $montantSalaireAnciennte), 2);
                    $montantSalaireBase  = round(($montantSalaireBase  - ($secondHalf + $minus)), 2);
                    $montantSalaireAnciennte = 0;
                }
                else {
                    $montantSalaireBase  = round(($montantSalaireBase - $secondHalf), 2);
                    $montantSalaireAnciennte = round(($montantSalaireAnciennte - $firstHalf), 2);
                }
                                    
                foreach($cotisations as $cotisation){
                    $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $cotisation->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);
                    // $excute = self::det_element_insert($id_employe,$element['Eléments'],$element['ID_Eléments'],abs($element['Montant_App_HRM']),NULL,$period,$code_comptable,$element['ID_Bulletin'],$id_cotis,$qte,$type,$this->em);
                    if(!$pcompteComptable) {
                        echo $cotisation->getRubrique()->getId() . '/' .$bordoreau->getNatureContract()->getId();
                        die;
                    }
                    $journalBulletinLg = new JournalBulletinLg();
                    $journalBulletinLg->setRubrique($cotisation->getRubrique());
                    $journalBulletinLg->setBulletin($bulletin);
                    $journalBulletinLg->setMontant($cotisation->getMontant());
                    $journalBulletinLg->setQte($pcompteComptable->getQte());
                    $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
                    $journalBulletinLg->setType($key);
                    $journalBulletinLg->setSens($pcompteComptable->getSens());

                    $this->entityManager->persist($journalBulletinLg);

                    $this->charge -= $journalBulletinLg->getMontant();
                }

                $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $salaireBase->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);
                // if($key == 'cimr') {
                //     echo $montantSalaireBaseInitial . '/' .$montantSalaireBase . '/' .$halfMontant;
                //     die;
                // }
                $journalBulletinLg = new JournalBulletinLg();
                $journalBulletinLg->setRubrique($salaireBase->getRubrique());
                $journalBulletinLg->setBulletin($bulletin);
                $journalBulletinLg->setMontant(round($montantSalaireBaseInitial - $montantSalaireBase, 2));
                $journalBulletinLg->setQte($pcompteComptable->getQte());
                $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
                $journalBulletinLg->setType($key);
                $journalBulletinLg->setSens($pcompteComptable->getSens());


                $this->entityManager->persist($journalBulletinLg);

                $this->charge += $journalBulletinLg->getMontant();

                if ($bordoreau->getNatureContract()->getType()->getId() == 1  && $salaireAnciennte && $bordoreau->getType() == 'paie') {
                    $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $salaireAnciennte->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);

                    $journalBulletinLg = new JournalBulletinLg();
                    $journalBulletinLg->setRubrique($salaireAnciennte->getRubrique());
                    $journalBulletinLg->setBulletin($bulletin);
                    $journalBulletinLg->setMontant(round($montantSalaireAnciennteInitital - $montantSalaireAnciennte, 2));
                    $journalBulletinLg->setQte($pcompteComptable->getQte());
                    $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
                    $journalBulletinLg->setType($key);
                    $journalBulletinLg->setSens($pcompteComptable->getSens());


                    $this->entityManager->persist($journalBulletinLg);
                    
                    $this->charge += $journalBulletinLg->getMontant();
                }


                $montantSalaireBaseInitial = $montantSalaireBase;
                $montantSalaireAnciennteInitital = $montantSalaireAnciennte;
            }
        }
        $type = "netpaye";

        $primesArrayInitital = [];
        // initialize the montant for each prime 
        foreach($primes as $prime) {
            array_push($primesArrayInitital, [
                'id' => $prime->getId(),
                'prime' => $prime,
                'montantInitial' => $prime->getMontant(),
                'montantFinal' => $prime->getMontant(),
                'montantMinus' => 0
            ]);
        }
        
        usort($primesArrayInitital, fn($a, $b) => $a['montantInitial'] <=> $b['montantInitial']);
        
        $halfMontantPretArray = [];
        // calculate the montant for each prelevement 
       
        $keyToRemove = 'montantFinal';

        // this array only for condition i keep the key to modify the montant thatt sould be removed in the original array primesArrayInitital
      
        
        foreach ($prevelementCabs as $key => $prevelementCab) {

            $filtredPrimesArrayInitital = array_filter($primesArrayInitital, function ($subarray) use ($keyToRemove) {
                return $subarray[$keyToRemove] != 0;
            });
            if($prevelementCab['montant'] > 0) {
                $nombrePrimes = count($filtredPrimesArrayInitital);
                
                $details = [];

                if(count($filtredPrimesArrayInitital) > 0) {
                    
                    $halfMontantPret = (($prevelementCab['montant'] / count($filtredPrimesArrayInitital)));
                    if($this->hasMoreThanTwoDecimals($halfMontantPret)) {
                        $halfMontantPret = $this->roundUpToNDecimals($halfMontantPret, 2);
                    }
                    $reste = 0;
                    $montantMinusInitial = 0;
                    $montantDebit = 0;
                    foreach ($filtredPrimesArrayInitital as $key => $primeArray) {
                        
                        if($primeArray['montantFinal'] == 0) {
                            $nombrePrimes = $nombrePrimes - 1;
                            $reste = $halfMontantPret - $primeArray['montantFinal'];
                            
                            if($nombrePrimes == 0) {
                                $halfReste = round(($reste / 1), 2);
                            } else {
                                $halfReste = round(($reste / $nombrePrimes), 2);
                            }
                            $halfMontantPret += $halfReste;
                          
                            $montant = 0;
                        }
                        
                        elseif($primeArray['montantFinal'] < $halfMontantPret) {
                            // echo $primeArray['montantFinal'] . '/'.$halfMontantPret;
                            // die;
                            $reste = $halfMontantPret - $primeArray['montantFinal'];
                            $primesArrayInitital[$key]['montantMinus'] = $primeArray['montantFinal'];
                            $montant = $primeArray['montantFinal'];
                            $primesArrayInitital[$key]['montantFinal'] = 0;
                            $montantMinusInitial += $montant;
                            $nombrePrimes = $nombrePrimes - 1;
                            if($nombrePrimes == 0) {
                                $halfReste = round(($reste / 1), 2);
                            } else {
                                $halfReste = ($reste / $nombrePrimes);
                                if($this->hasMoreThanTwoDecimals($halfReste)) {
                                    $halfReste = $this->roundUpToNDecimals($halfReste, 2);
                                }
                            }
                            $halfMontantPret += $halfReste;
                        } else {
                            if($prevelementCab['montant'] < $montantMinusInitial + $halfMontantPret) {
                                $halfMontantPret = round($prevelementCab['montant'] - $montantMinusInitial, 2);
                            }
                            
                            $montant = $halfMontantPret;
                            $montantMinusInitial += $montant;
                            

                            $primesArrayInitital[$key]['montantMinus'] = round($primesArrayInitital[$key]['montantMinus'] + $halfMontantPret, 2);
                            $primesArrayInitital[$key]['montantFinal'] = round($primesArrayInitital[$key]['montantFinal'] - $halfMontantPret, 2);
                            $filtredPrimesArrayInitital[$key]['montantMinus'] = round($filtredPrimesArrayInitital[$key]['montantMinus'] + $halfMontantPret, 2);
                            $filtredPrimesArrayInitital[$key]['montantFinal'] = round($filtredPrimesArrayInitital[$key]['montantFinal'] - $halfMontantPret, 2);
                        }

                        $montantDebit += $montant;

                        array_push($details, [
                            'prime' => $primeArray['prime'],
                            'montant' => $montant
                        ]);
                    }
                    // die;
                    // check if prelevement not completed from primes then debit from base and anciennete
                    $montantEcart = $prevelementCab['montant'] - $montantDebit;
                  
                    if($nombrePrimes == 0 and $montantEcart  > 0) {
                        $halfMontantPret = round($montantEcart, 2);
                        $halfMontant = round(($montantEcart / 2), 2);

                        if($halfMontant + $halfMontant != $halfMontantPret) {
                            $firstHalf = $halfMontant;
                            $secondHalf = $halfMontantPret - $halfMontant;
                        } else {
                            $firstHalf = $secondHalf = $halfMontant;
                        }

                        if ($firstHalf > $montantSalaireAnciennte) {
                            $minus = round(($firstHalf - $montantSalaireAnciennte), 2);
                            $montantSalaireBaseMinus  = round(($montantSalaireBase  - ($secondHalf + $minus)), 2);
                            $montantSalaireAnciennteMinus = 0;
                        }
                        else {
                            $montantSalaireBaseMinus  = round(($montantSalaireBase - $secondHalf), 2);
                            $montantSalaireAnciennteMinus = round(($montantSalaireAnciennte - $firstHalf), 2);
                        }

                

                        array_push($details, [
                            'prime' => $salaireBase,
                            'montant' => round($montantSalaireBase - $montantSalaireBaseMinus, 2)
                        ]);

                        if ($bordoreau->getNatureContract()->getType()->getId() == 1 and $salaireAnciennte and $bordoreau->getType() == 'paie') {
                            array_push($details, [
                                'prime' => $salaireAnciennte,
                                'montant' => round($montantSalaireAnciennte - $montantSalaireAnciennteMinus, 2)
                            ]);
                        }
    
                        $montantSalaireBase = $montantSalaireBaseMinus;
                        $montantSalaireAnciennte = $montantSalaireAnciennteMinus;
                    }
                } else {
                    // $montantSalaireBase;
                    // $montantSalaireAnciennteInitital;

                    $halfMontantPret = round($prevelementCab['montant'], 2);
                    $halfMontant = round(($prevelementCab['montant'] / 2), 2);

                    if($halfMontant + $halfMontant != $halfMontantPret) {
                        $firstHalf = $halfMontant;
                        $secondHalf = $halfMontantPret - $halfMontant;
                    } else {
                        $firstHalf = $secondHalf = $halfMontant;
                    }

                    if ($firstHalf > $montantSalaireAnciennte) {
                        $minus = round(($firstHalf - $montantSalaireAnciennte), 2);
                        $montantSalaireBaseMinus  = round(($montantSalaireBase  - ($secondHalf + $minus)), 2);
                        $montantSalaireAnciennteMinus = 0;
                    }
                    else {
                        $montantSalaireBaseMinus  = round(($montantSalaireBase - $secondHalf), 2);
                        $montantSalaireAnciennteMinus = round(($montantSalaireAnciennte - $firstHalf), 2);
                    }

             

                    array_push($details, [
                        'prime' => $salaireBase,
                        'montant' => round($montantSalaireBase - $montantSalaireBaseMinus, 2)
                    ]);
                    if ($bordoreau->getNatureContract()->getType()->getId() == 1 and $salaireAnciennte and $bordoreau->getType() == 'paie') {
                        array_push($details, [
                            'prime' => $salaireAnciennte,
                            'montant' => round($montantSalaireAnciennte - $montantSalaireAnciennteMinus, 2)
                        ]);
                    }

                    $montantSalaireBase = $montantSalaireBaseMinus;
                    $montantSalaireAnciennte = $montantSalaireAnciennteMinus;
                }
                
                array_push($halfMontantPretArray, [
                    'id' => $prevelementCab['id'],
                    'prelevement' => $prevelementCab,
                    'montant' => $halfMontantPret,
                    'montantInitial' => $prevelementCab['montant'],
                    'details' => $details
                ]);
                
            }
        }

        // echo json_encode($halfMontantPretArray);
        // die;
        foreach($primesArrayInitital as $primesArray ) {
            $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $primesArray['prime']->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);
            
            $journalBulletinLg = new JournalBulletinLg();
            $journalBulletinLg->setRubrique($primesArray['prime']->getRubrique());
            $journalBulletinLg->setBulletin($bulletin);
            if(count($halfMontantPretArray) == 0) {
                $journalBulletinLg->setMontant(round($primesArray['montantInitial'], 2));
            } else {
                $journalBulletinLg->setMontant(round($primesArray['montantFinal'], 2));
            }
            $journalBulletinLg->setQte($pcompteComptable->getQte());
            $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
            $journalBulletinLg->setType($type);
            $journalBulletinLg->setSens($pcompteComptable->getSens());

            $this->entityManager->persist($journalBulletinLg);

            $this->charge += $journalBulletinLg->getMontant();
        }

        foreach($prevelements as $prevelement){
            $rubrique = $this->entityManager->getRepository(Prubrique::class)->find($prevelement['rubrique_id']);
            $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $rubrique, 'natureContract' => $bordoreau->getNatureContract()]);
            
            $journalBulletinLg = new JournalBulletinLg();
            $journalBulletinLg->setRubrique($rubrique);
            $journalBulletinLg->setBulletin($bulletin);
            $journalBulletinLg->setMontant($prevelement['montant']);
            $journalBulletinLg->setQte($pcompteComptable->getQte());
            $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
            $journalBulletinLg->setType($type);
            $journalBulletinLg->setSens($pcompteComptable->getSens());
            if($bordoreau->getDevise() && $bordoreau->getDevise()->getId() != 1) {
                $journalBulletinLg->setMontantDevise($prevelement['montantDevise']);
            }
            $this->entityManager->persist($journalBulletinLg);

            $this->charge -= $journalBulletinLg->getMontant();

            
        }
            
        $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $salaireBase->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);

        $journalBulletinLg = new JournalBulletinLg();
        $journalBulletinLg->setRubrique($salaireBase->getRubrique());
        $journalBulletinLg->setBulletin($bulletin);
        $journalBulletinLg->setMontant(round($montantSalaireBase, 2));
        $journalBulletinLg->setQte($pcompteComptable->getQte());
        $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
        $journalBulletinLg->setType($type);
        $journalBulletinLg->setSens($pcompteComptable->getSens());
        if($bordoreau->getDevise() && $bordoreau->getDevise()->getId() != 1) {
            $journalBulletinLg->setMontantDevise($montantSalaireBaseDevise);
        }

        $this->entityManager->persist($journalBulletinLg);

        $this->charge += $journalBulletinLg->getMontant();

        if ($bordoreau->getNatureContract()->getType()->getId() == 1 and $bordoreau->getType() == 'paie') {
            $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $salaireAnciennte->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);

            $journalBulletinLg = new JournalBulletinLg();
            $journalBulletinLg->setRubrique($salaireAnciennte->getRubrique());
            $journalBulletinLg->setBulletin($bulletin);
            $journalBulletinLg->setMontant(round($montantSalaireAnciennte, 2));
            $journalBulletinLg->setQte($pcompteComptable->getQte());
            $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
            $journalBulletinLg->setType($type);
            $journalBulletinLg->setSens($pcompteComptable->getSens());

            $this->entityManager->persist($journalBulletinLg);

            $this->charge += $journalBulletinLg->getMontant();
        }
            
        foreach($halfMontantPretArray as $halfMontantPret){
            $rubrique = $this->entityManager->getRepository(Prubrique::class)->find($halfMontantPret['prelevement']['rubrique_id']);
            $type = $rubrique->getDesignation();
            foreach($halfMontantPret['details'] as $det) {
                
                $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $det['prime']->getRubrique(), 'natureContract' => $bordoreau->getNatureContract()]);
                $journalBulletinLg = new JournalBulletinLg();
                $journalBulletinLg->setRubrique($det['prime']->getRubrique());
                $journalBulletinLg->setBulletin($bulletin);
                $journalBulletinLg->setMontant($det['montant']);
                $journalBulletinLg->setQte($pcompteComptable->getQte());
                $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
                $journalBulletinLg->setType($type);
                $journalBulletinLg->setSens($pcompteComptable->getSens());

                $this->entityManager->persist($journalBulletinLg);

                $this->charge += $journalBulletinLg->getMontant();

            }
            
            $pcompteComptable = $this->entityManager->getRepository(PCompteComptable::class)->findOneBy(['rubrique' => $rubrique, 'natureContract' => $bordoreau->getNatureContract()]);
            $journalBulletinLg = new JournalBulletinLg();
            $journalBulletinLg->setRubrique($rubrique);
            $journalBulletinLg->setBulletin($bulletin);
            $journalBulletinLg->setMontant($halfMontantPret['montantInitial']);
            $journalBulletinLg->setQte($pcompteComptable->getQte());
            $journalBulletinLg->setCodeComptable($pcompteComptable->getCompteComptable());
            $journalBulletinLg->setType($type);
            $journalBulletinLg->setSens($pcompteComptable->getSens());

            $this->entityManager->persist($journalBulletinLg);

            $this->charge -= $journalBulletinLg->getMontant();


            
        }  
    }
   
    
    function hasMoreThanTwoDecimals($number) {
        // Convert the number to a string to handle both integers and floats
        $numberStr = strval($number);
    
        // Use a regular expression to check if the number has more than two decimals
        // The pattern matches a dot (decimal point), followed by at most two digits
        return preg_match('/\.\d{3,}/', $numberStr) === 1;
    }

    function roundUpToNDecimals($number, $decimals) {
        $multiplier = pow(10, $decimals);
        return ceil($number * $multiplier) / $multiplier;
    }
   
}