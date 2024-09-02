<?php

namespace App\Repository;

use App\Entity\Checkinout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Checkinout>
 *
 * @method Checkinout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Checkinout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Checkinout[]    findAll()
 * @method Checkinout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CheckinoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Checkinout::class);
    }

//    /**
//     * @return Checkinout[] Returns an array of Checkinout objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Checkinout
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function findCheckinoutByPeriode($dateDebut,$dateFin)
   {
       return $this->createQueryBuilder('checkinout')
            ->select("employe.id as ID_EMPLOYE, employe.nom as NOM, employe.prenom as PRENOM, sit.designation as SITE, ctr.code CODE_CONTRACT, ctr.fonction as FONCTION, checkinout.sn as SN, checkinout.checktime ")
            ->innerJoin('checkinout.userinfo', 'userinfo')
            ->innerJoin('userinfo.street', 'employe')
            ->innerJoin('employe.contracts', 'ctr')
            ->innerJoin('ctr.site', 'sit')
            ->Where('checkinout.checktime BETWEEN :dateDebut AND :dateFin')
            ->setParameter('dateDebut', $dateDebut.' 00:00:00')
            ->setParameter('dateFin', $dateFin.' 23:59:59')
            ->orderBy('checkinout.checktime', 'DESC')
            // ->setMaxResults(10)
            ->getQuery()
            ->getResult()
       ;
   }
}
