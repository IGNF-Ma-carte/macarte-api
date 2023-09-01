<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @param array $criteria['query', 'userId', 'limit', 'offset']
     *      
     */
    public function research(array $criteria){
        return array(
            'medias' => $this->getMedias($criteria),
            'count' => $this->getMedias($criteria, true),
            'limit' => $criteria['limit'] ?: 15,
            'offset' => $criteria['offset'] ?: 0,
        );
    }

    private function getMedias(array $criteria, $countOnly = false){
        $qb = $this->createQueryBuilder('m');

        if(isset($criteria['userId']) and $criteria['userId']){
            $qb->andWhere('m.owner = :userId')
                ->setParameter('userId', $criteria['userId'])
            ;
        }
        if(isset($criteria['name']) and $criteria['name']){
            $qb->andWhere('LOWER(m.name) LIKE LOWER(:name)')
                ->setParameter('name', '%'.$criteria['name'].'%')
            ;
        }
        if(isset($criteria['folder']) and $criteria['folder']){
            $qb->andWhere('m.folder LIKE :folder')
                ->setParameter('folder', '%'.$criteria['folder'].'%')
            ;
        }
        if(isset($criteria['valid']) and $criteria['valid'] !== null){
            $qb->andWhere('m.valid = :valid')
                ->setParameter('valid', $criteria['valid'])
            ;
        }
        if(isset($criteria['id']) and $criteria['id']){
            $qb->andWhere('m.id = :id')
                ->setParameter('id', $criteria['id'])
            ;
        }

        if($countOnly){
            $qb->select('count(0)');
            return $qb->getQuery()->getSingleScalarResult();
        }

        if($criteria['limit']){
            $qb->setMaxResults($criteria['limit']);
        }
        if($criteria['offset']){
            $qb->setFirstResult($criteria['offset']);
        }
        if($criteria['sort'] == 'size'){
            $qb->orderBy('m.size', 'DESC');
        }else{
            $qb->orderBy('m.uploadedAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function findFolders($userId){
        $qb = $this->createQueryBuilder('m')
            ->select('m.folder')
            ->where('m.owner = :userId')
            ->setParameter('userId', $userId)
            ->distinct()
            ->getQuery()
            ;
        $result = $qb->getResult();

        return array_column($result, 'folder');
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist(Media $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

        /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Media $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Media[] Returns an array of Media objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Media
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
