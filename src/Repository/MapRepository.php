<?php

namespace App\Repository;

use Exception;
use App\Entity\Map;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Map|null find($id, $lockMode = null, $lockVersion = null)
 * @method Map|null findOneBy(array $criteria, array $orderBy = null)
 * @method Map[]    findAll()
 * @method Map[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Map::class);
    }


    /**
     * documentation http://rachbelaid.com/postgres-full-text-search-is-good-enough/
     * $research = null, critère de recherche
     * $user = null, nom public du createur des cartes ou null
     * $theme = null, nom du theme des cartes, 'not-defined' ou null
     * $type = null, type des cartes 'map', 'storymap' ou null
     * $premium = null, valeur du premium des cartes ou null
     * $sort = null, 
     * $offset = 0, 
     * $limit = 15, 
     * $active = null, 
     * $valid = null, 
     * $share = null, 
     * $mustHaveTheme = true, 
     * $canBePrivate = false
     * 
     * @return array 
     *       'maps' => liste des <limit> cartes à partir de <offset>
     *       'users' => liste des utilisateurs + nb de cartes (10 max)
     *       'themes' => liste des themes + nb de cartes 
     *       'premiums' => liste des premiums + nb de cartes 
     *       'types' => liste des types + nb de cartes
     *       'valides' => liste des types + nb de cartes
     *       'actives' => liste des types + nb de cartes
     *       'types' => liste des types + nb de cartes
     *       'count' => nb de cartes total répondant aux critères
     *       'limit' => nb de cartes à renvoyer
     *       'offset' => nb de cartes passées avant de les renvoyer
     *       'query' => terme recherché
     */
    public function searchFullText(
        $research = null, $user = null, $theme = null, $type = null, $premium = null, 
        $active = null, $valid = null,
        $sort = null, $offset = 0, $limit = 15, 
        $mustHaveTheme = true, $share = false
    ){

        $select = "SELECT m_search.*, ts_rank(m_search.document, to_tsquery(unaccent(:query))) into TEMPORARY tmp";
        /****************************************************************************************************/
        /* ATTENTION                                                                                        */
        /* N'utiliser le $select SANS TEMPORARY que dans les dev pour vérifier la structure de la table tmp */
        /* Penser à supprimer la table tmp entre chaque test                                                */
        /****************************************************************************************************/
        // $select = "SELECT m_search.*, ts_rank(m_search.document, to_tsquery(unaccent(:query))) into  tmp";
        
        $wheres = array();
        if($research){
            $wheres[] = 'm_search.document @@ to_tsquery(unaccent(:query)) ';
        }
        if($user){
            $wheres[] = 'm_search.user like :user';
        }
        if($theme){
            if($theme == 'undefined'){
                $wheres[] = 'theme IS NULL';
            }else{
                $wheres[] = 'theme like :theme';
            }
        }
        switch($type){
            case 'storymap':
                $wheres[] = "type like 'storymap'";
                break;
            case 'map':
                $wheres[] = "type not like 'storymap'";
                break;
        }
        if($premium){
            if($premium == 'not-defined'){
                $wheres[] = 'premium IS NULL';
            }else{
                $wheres[] = 'm_search.premium like :premium';
            }
        }

        if($active !== null){
            $wheres[] = 'active = :active';
        }
        if($valid !== null){
            $wheres[] = 'valide = :valid';
        }

        $sql = $this->getSqlFulltext($select, $wheres, null, $sort, null, $mustHaveTheme, $share );

        //création de la table temporaire comprenant toutes les cartes répondant aux critères de recherche
        $rsm = new ResultSetMapping();
        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameter(':query', $research)
        ;
        if($user){
            $query->setParameter(':user', $user);
        }
        if($theme){
            $query->setParameter(':theme', $theme);
        }
        if($type){
            $query->setParameter('type', $type);
        }
        if($premium){
            $query->setParameter('premium', $premium);
        }
        if($active !== null){
            $query->setParameter('active', $active ? 1 : 0);
        }
        if($valid !== null){
            $query->setParameter('valid', $valid ? 1 : 0);
        }

        $query->getResult();

        //récupération des 10 utilisateurs avec le plus de cartes
        $sqlUsers = 'SELECT tmp.user, count(0) 
            FROM tmp  
            GROUP BY tmp.user
            ORDER BY count(0) DESC
            LIMIT 10
        ';

        //récupération des <limit> cartes
        $sqlMaps = "SELECT * FROM tmp ";
        if($offset){
            $sqlMaps .=  " OFFSET " . intval($offset);
        }
        if($limit){
            $sqlMaps .=  " LIMIT " . intval($limit);
        }

        // récupération des themes classés par nb de cartes
        $sqlThemes = "SELECT coalesce(theme, 'undefined') AS theme, count(0)
            FROM tmp  
            GROUP BY theme 
            ORDER BY count(0) DESC"
        ;

        //recupération des premium
        $sqlPremium = 'SELECT tmp.premium, count(0) 
            FROM tmp 
            GROUP BY premium 
            ORDER BY count(0) DESC'
        ;
        $sqlShare = 'SELECT tmp.share, count(0) 
        FROM tmp 
        GROUP BY share 
        ORDER BY count(0) DESC';
                
        // récupération du nb de cartes actives total
        $sqlActive = "SELECT active, count(0) FROM tmp GROUP BY active ";

        // récupération du nb de cartes valides total
        $sqlValid = "SELECT valide as valid, count(0) FROM tmp GROUP BY valide ";

        // récupération du nb de cartes total
        $sqlMapsCount = "SELECT count(0) FROM tmp WHERE type NOT LIKE 'storymap'";
        $nbMaps = $this->executeStatement($sqlMapsCount, false);

        // récupération du nb de cartes narratives total
        $sqlStorymapsCount = "SELECT count(0) FROM tmp WHERE type LIKE 'storymap'";
        $nbStorymaps = $this->executeStatement($sqlStorymapsCount, false);

        $macarteType = new \stdClass();
        $macarteType->type = 'macarte';
        $macarteType->count = $nbMaps['count'];

        $storymapType = new \stdClass();
        $storymapType->type = 'storymap';
        $storymapType->count = $nbStorymaps['count'];

        $types = array();
        if($macarteType->count){
            $types[] = $macarteType;
        }
        if($storymapType->count){
            $types[] = $storymapType;
        }

        return array(
            'maps' => $this->executeStatement($sqlMaps, true),
            'themes' => $this->executeStatement($sqlThemes, true),
            'users' => $this->executeStatement($sqlUsers, true),
            'types' => $types,
            'premiums' => $this->executeStatement($sqlPremium, true),
            'actives' => $this->executeStatement($sqlActive, true),
            'valides' => $this->executeStatement($sqlValid, true),
            'shares' => $this->executeStatement($sqlShare, true),
            'query' => $research,
            'count' => $nbMaps['count'] + $nbStorymaps['count'],
            'limit' => $limit,
            'offset' => $offset,
        );

    }

    private function executeStatement($sql, $all = false){
        $statement = $this->_em->getConnection()->prepare($sql);
        $result = $statement->executeQuery();
        if($all){
            return $result->fetchAllAssociative(); 
        }
        return $result->fetchAssociative(); 
    }

    private function getSqlFulltext(
        $select, $wheres, $orderBy = null, $sort = null, $limit = null, 
        $mustHaveTheme = true, $share = null
    ){

        //suivant le contexte, on adapte le where du from(select...)
        $wheresFrom = [];

        if($share){
            $wheresFrom[] = " (m.share LIKE '".$share."')";
        }

        if($mustHaveTheme){
            $selectTheme = " to_tsvector(unaccent(t.name)) ";
            $wheresFrom[] = " m.theme_id IS NOT NULL";
        }else{
            $selectTheme = " to_tsvector(unaccent( coalesce(t.name, ' '))) " ;
        }


        // on peut définir un order by spécifique OU un ordre de tri
        if(!$orderBy){
            $orderBy = ' ORDER BY ';
            switch($sort){
                case 'rank': 
                    $orderBy .= 'ts_rank(m_search.document, to_tsquery(unaccent(:query))) DESC, m_search.maj DESC, m_search.nb_view DESC, ';
                    break;
                case 'date':
                    $orderBy .= 'm_search.maj DESC, ';
                    break;
                case 'views':
                    $orderBy .= 'm_search.nb_view DESC, m_search.maj DESC, ';
                    break;
            }
            $orderBy .= ' m_search.titre ASC, m_search.id ASC ';
        }   

        $sql = 
$select
." FROM (
	SELECT 
        m.*,
        u.public_name AS user,
        u.public_id AS user_id,
        t.name AS theme,
		to_tsvector(unaccent(m.titre)) 
            || to_tsvector(unaccent(m.description)) 
            || ".$selectTheme."
            AS document
    FROM map AS m
    LEFT JOIN theme t ON m.theme_id = t.id
    JOIN utilisateurs u ON m.creator_id = u.id ";
for( $i = 0; $i < sizeof($wheresFrom); $i++){
    if($i === 0){
        $sql .= ' WHERE ';
    }else{
        $sql .= ' AND ';
    }
    $sql .= $wheresFrom[$i];
}
$sql .= ") m_search ";
for( $i = 0; $i < sizeof($wheres); $i++){
    if($i === 0){
        $sql .= ' WHERE ';
    }else{
        $sql .= ' AND ';
    }
    $sql .= $wheres[$i];
}

$sql .= $orderBy;

if($limit){
    $sql .= ' LIMIT '.$limit;
}

        return $sql;
    }

    public function searchFullTextUser($research, $user, $theme = null, $limit = 8){
        $select = "SELECT DISTINCT m_search.user";

        $wheres = array();
        if($research){
            $wheres[] = 'm_search.document @@ to_tsquery(unaccent(:query)) ';
        }
        $wheres[] = 'lower(unaccent(m_search.user)) like lower(unaccent(:user))';
        if($theme){
            if($theme == 'not-defined'){
                $wheres[] = 'theme IS NULL';
            }else{
                $wheres[] = 'theme like :theme';
            }
        }

        $orderBy = " ORDER BY m_search.user  asc";

        $sql = $this->getSqlFulltext($select, $wheres, $orderBy, null, $limit);

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('user', 'u');

        $query = $this->_em->createNativeQuery($sql, $rsm)
            ->setParameter(':query', $research)
            ->setParameter(':user', '%'.$user.'%')
        ;
        if($theme){
            $query->setParameter(':theme', $theme);
        }
        
        $users = $query->getResult();
        
        $result = array();
        /*
         * TODO mettre à jour la requete pour avoir directement le tableau d'utilisateurs
         * Décodage de $result
         * $users est de la forme array(
         *      array('u' => 'myUsername'),
         *      array('u' => 'myUsername2'),
         * )
         */ 
        foreach($users as $user){
            $result[] = $user['u'];
        }

        return $result;
    }

    
    public function findByIdUnique($id){
        return $this->createQueryBuilder('m')
            ->where('m.idView LIKE :id')
            ->orWhere('m.idEdit LIKE :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist(Map $entity, bool $flush = true): void
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
    public function remove(Map $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }





    // /**
    //  * @return Map[] Returns an array of Map objects
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
    public function findOneBySomeField($value): ?Map
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
