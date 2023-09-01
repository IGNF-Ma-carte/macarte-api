<?php

namespace App\Repository;

use App\Entity\MapViewTracking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MapViewTracking>
 *
 * @method MapViewTracking|null find($id, $lockMode = null, $lockVersion = null)
 * @method MapViewTracking|null findOneBy(array $criteria, array $orderBy = null)
 * @method MapViewTracking[]    findAll()
 * @method MapViewTracking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapViewTrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapViewTracking::class);
    }

    public function removeBefore(\DateTime $date){
        $qb = $this->createQueryBuilder('m');
        $qb->andWhere('m.date < :date')
            ->setParameter('date', $date->format('Y-m-d'))
        ;

        $tracks = $qb->getQuery()->getResult();

        foreach($tracks as $track){
            $this->_em->remove($track);
        }
        $this->_em->flush();
    }

    public function persist(MapViewTracking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MapViewTracking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MapViewTracking[] Returns an array of MapViewTracking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MapViewTracking
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
