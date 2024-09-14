<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findTransactions(): array
    {

        return $this->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();

        // return $this->createQuery(
        //     'SELECT u
        //      FROM App\Entity\User u'
        // )->setMaxResults(15)
        // ->setFirstResult(($page-1)*15)
        // ->getResult();

    }

    public function findNbTotalTransactions(int $category): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastTransactionsByProject(Project $project, int $category): array
    {
        $result = [];
        $now = new \DateTime();
        
        for ($i = 5; $i >= 0; $i--) {
            $startOfMonth = (clone $now)->modify("-$i months")->modify('first day of this month')->setTime(0, 0, 0);
            $endOfMonth = (clone $startOfMonth)->modify('last day of this month')->setTime(23, 59, 59);

            $query = $this->createQueryBuilder('t')
                ->select('SUM(t.amount) as total')
                ->where('t.project = :project')
                ->andWhere('t.date BETWEEN :start AND :end')
                ->andWhere('t.category = :category')
                ->setParameter('category', $category)
                ->setParameter('project', $project)
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery();

            $total = $query->getSingleScalarResult();
            $result[] = $total ? (float)$total : 0.0;
        }

        return $result;
    }

    public function findUserTransactions(User $user, int $category) : float
    {
        $query = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->andWhere('t.category = :category')
            ->andWhere('t.user = :user')
            ->setParameter('category', $category)
            ->setParameter('user', $user)
            ->getQuery();


            $total = $query->getSingleScalarResult();

            return $total ? (float)$total : 0.0;
    }

    public function getTotalAmountProjectInvestments(Project $project): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as totalInvest')
            ->where('t.category = :category')
            ->andWhere('t.project = :project')
            ->setParameter('category', 1)
            ->setParameter('project', $project)
            ->getQuery();

        $result = $qb->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    public function findTransactionsByFilters($data, bool $count = false) : array | int
    {

        $query = $this->createQueryBuilder('t');

        if ($count) {
            $query->select('count(t.id)');
        }

        $query->leftJoin('t.user', 'ut');

        if ($data['category']) {
            $query->andWhere('t.category = :category')
            ->setParameter('category', $data['category']);
        }

        if (isset($data['datemin']) && !empty($data['datemin'])) {
            $datemin = $data['datemin'];
            $query->andWhere('t.date >= :datemin')
                  ->setParameter('datemin', $datemin);
        }
        if (isset($data['datemax']) && !empty($data['datemax'])) {
            $datemax = $data['datemax'];
            $query->andWhere('t.date <= :datemax')
                  ->setParameter('datemax', $datemax);
        }
        if (isset($data['name']) && !empty($data['name'])) {
            $query->andWhere('ut.lastname LIKE :name OR ut.firstname LIKE :name')
                  ->setParameter('name', '%' . $data['name'] . '%');
        }
        if (isset($data['amountmin']) && !empty($data['amountmin'])) {
            $query->andWhere('t.amount >= :amountmin')
                  ->setParameter('amountmin', $data['amountmin']);
        }
        if (isset($data['amountmax']) && !empty($data['amountmax'])) {
            $query->andWhere('t.amount <= :amountmax')
                  ->setParameter('amountmax', $data['amountmax']);
        }
        
        if (isset($data['orderId']) && !empty($data['orderId'])) {
            $query->orderBy('t.id', $data['order']);
        }
        if (isset($data['orderDate']) && !empty($data['orderDate'])) {
            $query->orderBy('t.date', $data['order']);
        }
        if (isset($data['orderLastname']) && !empty($data['orderLastname'])) {
            $query->orderBy('ut.lastname', $data['order']);
        }
        if (isset($data['orderAmount']) && !empty($data['orderAmount'])) {
            $query->orderBy('t.amount', $data['order']);
        }

        $page = $data['page'] ? $data['page'] : 1;

        if( !$count ){
            $query->setMaxResults(Project::MAXPERPAGE)
                    ->setFirstResult(($page-1)*Transaction::MAXPERPAGE);
        }

        return $count ? $query->getQuery()->getSingleScalarResult() : $query->getQuery()->getResult();
    }

    public function findUserTransactionsByDate(User $user, $startDate, $endDate, int $category)
    {
        // Génération des mois
        $allMonths = $this->generateMonths($startDate, $endDate);

        $qb = $this->createQueryBuilder('t');
        $qb->select('YEAR(t.date) AS year', 'MONTH(t.date) AS month', 'SUM(t.amount) AS totalAmount')
           ->where('t.date >= :startDate AND t.date <= :endDate')
           ->andWhere('t.category = :category')
           ->andWhere('t.user = :user')
           ->setParameter('startDate', new \DateTime($startDate))
           ->setParameter('endDate', new \DateTime($endDate))
           ->setParameter('category', $category)
           ->setParameter('user', $user)
           ->groupBy('year')
           ->addGroupBy('month')
           ->orderBy('year', 'ASC')
           ->addOrderBy('month', 'ASC');
        
        $results = $qb->getQuery()->getResult();

        // Fusion des résultats avec la liste des mois
        foreach ($results as $result) {
            $key = $result['year'] . '-' . sprintf('%02d', $result['month']);
            if (isset($allMonths[$key])) {
                $allMonths[$key]['totalAmount'] = $result['totalAmount'];
            }
        }

        $result = [];
        foreach($allMonths as $al){
            $result[] = $al['totalAmount'];
        }

        return $result;

    }

    public function generateMonths($startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = \DateInterval::createFromDateString('1 month');
        $period = new \DatePeriod($start, $interval, $end->modify('+1 month'));
    
        $months = [];
        foreach ($period as $dt) {
            $months[$dt->format("Y-m")] = ['year' => $dt->format("Y"), 'month' => $dt->format("m"), 'totalAmount' => 0];
        }
        return $months;
    }

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
