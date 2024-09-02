<?php

namespace App\Repository;

use App\Entity\UsModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UsModule>
 *
 * @method UsModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsModule[]    findAll()
 * @method UsModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsModule::class);
    }

    public function add(UsModule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UsModule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function getModuleBySousModule($sousModule) {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.sousModules', "sousModule")
            ->where('sousModule in (:sousModule)')
            ->setParameter('sousModule', $sousModule)
            ->orderBy("u.ordre", 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return UsModule[] Returns an array of UsModule objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UsModule
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
