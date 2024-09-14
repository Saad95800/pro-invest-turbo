<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Transaction;
use App\Entity\User;

class TransactionService {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function calculateBalance(User $user) {
        $deposits = $this->entityManager->getRepository(Transaction::class)->findUserTransactions($user, 2);
        $dividends = $this->entityManager->getRepository(Transaction::class)->findUserTransactions($user, 4);
        $investments = $this->entityManager->getRepository(Transaction::class)->findUserTransactions($user, 1);
        $withdraws = $this->entityManager->getRepository(Transaction::class)->findUserTransactions($user, 3);

        return $deposits + $dividends - $investments - $withdraws;
    }
}
