<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    // public function findProjects(int $page): array
    // {

    //     return $this->createQueryBuilder('p')
    //         ->setMaxResults(Project::MAXPERPAGE)
    //         ->setFirstResult(($page-1)*Project::MAXPERPAGE)
    //         ->orderBy('p.date', 'DESC')
    //         ->getQuery()
    //         ->getResult();

    //     return $this->createQuery(
    //         'SELECT u
    //          FROM App\Entity\Project p'
    //     )->setMaxResults(30)
    //     ->setFirstResult(($page-1)*30)
    //     ->getResult();

    // }

    // public function findNbTotalProjects(): int
    // {
    //     return (int) $this->createQueryBuilder('p')
    //         ->select('COUNT(p.id)')
    //         ->getQuery()
    //         ->getSingleScalarResult();
    // }

    public function findProjectsByFilters(array $data, bool $count = false) : array | int
    {

        $query = $this->createQueryBuilder('p');

        if ($count) {
            $query->select('count(p.id)');
        }

        if (isset($data['name']) && !empty($data['name'])) {
            $query->andWhere('p.name LIKE :name')
                  ->setParameter('name', '%' . $data['name'] . '%');
        }
        if (isset($data['agemin']) && !empty($data['agemin'])) {
            $query->andWhere('p.age >= :agemin')
                  ->setParameter('agemin', $data['agemin']);
        }
        if (isset($data['agemax']) && !empty($data['agemax'])) {
            $query->andWhere('p.age <= :agemax')
                  ->setParameter('agemax', $data['agemax']);
        }
        if (isset($data['yieldmin']) && !empty($data['yieldmin'])) {
            $query->andWhere('p.yield >= :yieldmin')
                  ->setParameter('yieldmin', $data['yieldmin']);
        }

        if (isset($data['yieldmax']) && !empty($data['yieldmax'])) {
            $query->andWhere('p.yield <= :yieldmax')
                  ->setParameter('yieldmax', $data['yieldmax']);
        }
        if (isset($data['risk']) && !empty($data['risk'])) {
            $query->andWhere('p.risk = :risk')
                  ->setParameter('risk', $data['risk']);
        }


        if (isset($data['orderId']) && !empty($data['orderId'])) {
            $query->orderBy('p.id', $data['order']);
        }
        if (isset($data['orderName']) && !empty($data['orderName'])) {
            $query->orderBy('p.name', $data['order']);
        }
        if (isset($data['orderAge']) && !empty($data['orderAge'])) {
            $query->orderBy('p.age', $data['order']);
        }
        if (isset($data['orderYield']) && !empty($data['orderYield'])) {
            $query->orderBy('p.yield', $data['order']);
        }
        if (isset($data['orderRisk']) && !empty($data['orderRisk'])) {
            $query->orderBy('p.risk', $data['order']);
        }

        $page = $data['page'] ? $data['page'] : 1;

        if( !$count ){
            $query->setMaxResults(Project::MAXPERPAGE)
                    ->setFirstResult(($page-1)*Project::MAXPERPAGE);
        }

        return $count ? $query->getQuery()->getSingleScalarResult() : $query->getQuery()->getResult();
    }

    //    /**
    //     * @return Project[] Returns an array of Project objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Project
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
