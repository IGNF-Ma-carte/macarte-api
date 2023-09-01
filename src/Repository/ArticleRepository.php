<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function getNextPosition($category){
        $lastArticle = $this->findOneBy(array('category' => $category), array('position' => 'DESC'));
        if(!$lastArticle){
            return 1;
        }
        return $lastArticle->getPosition() + 1;
    }

    public function findNext(Article $article)
    {
        return $this->findOneBy(
            [
                'position' => $article->getPosition() + 1,
                'category' => $article->getCategory(),
            ]
        );
    }

    /**
     * Find previous article
     */
    public function findPrevious(Article $article)
    {
        return $this->findOneBy(
            [
                'position' => $article->getPosition() - 1,
                'category' => $article->getCategory(),
            ]
        );
    }

    /**
     * Déplace l'article en première position et persist l'article
     */
    public function moveToFirstPosition(Article $article)
    {
        if (1 == $article->getPosition()) {
            return;
        }

        // reorder articles
        $query = $this->createQueryBuilder('a')
            ->update()
            ->set('a.position', 'a.position + 1')
            ->where('a.category = :category')
            ->setParameter('category', $article->getCategory())
            ->getQuery();
        $query->execute();

        $article->setPosition(1);
        $this->persist($article);

        return $article;
    }

    /**
     * diminue la position de 1 pour les articles de l'ancienne categorie situés après l'article
     * augmente la position de 1 pour les articles de la nouvelle categorie
     * met l'article en position 1
     * persist l'article
     *
     * @param Article $article
     * @param string $oldCategory
     * @return Article
     */
    public function updatePositionsForNewCategory(Article $article, $oldCategory){
        // diminue la position de 1 pour les articles de l'ancienne categorie situés après l'article
        $query = $this->createQueryBuilder('a')
            ->update()
            ->set('a.position', 'a.position - 1')
            ->where('a.category = :category')
            ->setParameter('category', $oldCategory)
            ->andWhere('a.position > :position')
            ->setParameter('position', $article->getPosition())
            ->getQuery();
        $query->execute();

        // augmente la position de 1 pour les articles de la nouvelle categorie
        $query = $this->createQueryBuilder('a')
            ->update()
            ->set('a.position', 'a.position + 1')
            ->where('a.category = :category')
            ->setParameter('category', $article->getCategory())
            ->getQuery();
        $query->execute();

        // met l'article en position 1
        $article->setPosition(1);
        $this->persist($article);

        return $article;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist(Article $entity, bool $flush = true): void
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
    public function remove(Article $article, bool $flush = true): void
    {
        $this->_em->remove($article);

        $query = $this->createQueryBuilder('a')
            ->update()
            ->set('a.position', 'a.position - 1')
            ->where('a.category = :category')
            ->andWhere('a.position > :position')
            ->setParameter('category', $article->getCategory())
            ->setParameter('position', $article->getPosition())
            ->getQuery();
        $query->execute();
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Article[] Returns an array of Article objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
