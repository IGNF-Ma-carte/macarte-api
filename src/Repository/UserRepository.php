<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function research($criteria){
        return array(
            'users' => $this->getUsers($criteria),
            'count' => $this->getUsers($criteria, true),
            'limit' => $criteria['limit'] ?: 15,
            'offset' => $criteria['offset'] ?: 0,
        );
    }

    public function countGlobal(){
        return $this->getUsers(array(), true);
    }

    private function getUsers($criteria, $countOnly = false){
        $qb = $this->createQueryBuilder('u');

        if(isset($criteria['id']) and $criteria['id']){
            $qb->andWhere('u.id = :id')
                ->setParameter('id', $criteria['id'])
            ;
        }

        if(isset($criteria['query']) and $criteria['query']){
            $qb->andWhere('LOWER(u.username) LIKE :query OR LOWER(u.email) LIKE :query or LOWER(u.publicName) LIKE :query')
                ->setParameter('query', '%'.strtolower($criteria['query']).'%')
            ;
        }
        if(isset($criteria['role']) and $criteria['role']){
            if($criteria['role'] == 'ROLE_USER'){
                $roles = User::getRoleNames();
                unset($roles['ROLE_USER']);

                foreach($roles as $key => $value ){
                    $qb->andWhere("u.roles NOT LIKE '%".$key."%'");
                }
            }else{
                $qb->andWhere("u.roles LIKE :role")
                    ->setParameter('role', '%'.$criteria['role'].'%');
            }
        }
        if(isset($criteria['locked']) and $criteria['locked'] == 'true'){
            $qb->andWhere("u.locked = TRUE");
        }

        if($countOnly){
            $qb->select('count(0)');
            return $qb->getQuery()->getSingleScalarResult();
        }

        if(isset($criteria['limit']) and $criteria['limit']){
            $qb->setMaxResults($criteria['limit']);
        }
        if(isset($criteria['offset']) and $criteria['offset']){
            $qb->setFirstResult($criteria['offset']);
        }
        
        $qb->orderBy('u.username', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        /*if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();*/
    }

    public function findOneByEmailOrUsername($query){
        return $this->createQueryBuilder('u')
            ->where('u.username = :query')
            ->orWhere('u.email = :query')
            ->setParameter('query', $query)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist(User $entity, bool $flush = true): void
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
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
