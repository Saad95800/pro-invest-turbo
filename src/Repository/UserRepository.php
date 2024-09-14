<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findUsers(int $page): array
    {

        return $this->createQueryBuilder('u')
            ->setMaxResults(User::MAXPERPAGE)
            ->setFirstResult(($page-1)*User::MAXPERPAGE)
            ->orderBy('u.createDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->createQuery(
            'SELECT u
             FROM App\Entity\User u'
        )->setMaxResults(30)
        ->setFirstResult(($page-1)*30)
        ->getResult();

    }

    public function findNbTotalUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUsersByFilters($data, bool $count = false) : array | int
    {

        $query = $this->createQueryBuilder('u');

        if ($count) {
            $query->select('count(u.id)');
        }

        if (isset($data['email']) && !empty($data['email'])) {
            $query->andWhere('u.email LIKE :email')
                  ->setParameter('email', '%' . $data['email'] . '%');
        }
        if (isset($data['phone']) && !empty($data['phone'])) {
            $query->andWhere('u.phone LIKE :phone')
                  ->setParameter('phone', '%' . $data['phone'] . '%');
        }

        if (isset($data['firstname']) && !empty($data['firstname'])) {
            $query->andWhere('u.firstname LIKE :firstname')
                  ->setParameter('firstname', $data['firstname'] . '%');
        }
        if (isset($data['lastname']) && !empty($data['lastname'])) {
            $query->andWhere('u.lastname LIKE :lastname')
                  ->setParameter('lastname', $data['lastname'] . '%');
        }


        if (isset($data['orderId']) && !empty($data['orderId'])) {
            $query->orderBy('u.id', $data['order']);
        }
        if (isset($data['orderFirstname']) && !empty($data['orderFirstname'])) {
            $query->orderBy('u.firstname', $data['order']);
        }
        if (isset($data['orderLastname']) && !empty($data['orderLastname'])) {
            $query->orderBy('u.lastname', $data['order']);
        }
        if (isset($data['orderEmail']) && !empty($data['orderEmail'])) {
            $query->orderBy('u.email', $data['order']);
        }
        if (isset($data['orderPhone']) && !empty($data['orderPhone'])) {
            $query->orderBy('u.phone', $data['order']);
        }

        $page = $data['page'] ? $data['page'] : 1;

        if( !$count ){
            $query->setMaxResults(User::MAXPERPAGE)
                    ->setFirstResult(($page-1)*User::MAXPERPAGE);
        }

        return $count ? $query->getQuery()->getSingleScalarResult() : $query->getQuery()->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
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

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
