<?php

namespace App\Repository;

use App\Entity\Comments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Comments|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comments|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comments[]    findAll()
 * @method Comments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Comments::class);
    }

    public function getwithuser($productid): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT comments.*,user.name as u_name from comments  left join user on comments.userid = user.id where comments.productid= :productid' ;
        $stmt = $conn->prepare($sql);
        $stmt->execute(["productid"=> $productid]);
        return $stmt->fetchAll();
    }

    public function getwithproduct($userid): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT comments.*,product.description as p_desc,product.image as p_img from comments  left join product on comments.productid = product.id where comments.userid=:id' ;
        $stmt = $conn->prepare($sql);
        $stmt->execute(["id"=>$userid]);
        return $stmt->fetchAll();
    }

//    /**
//     * @return Comments[] Returns an array of Comments objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Comments
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
