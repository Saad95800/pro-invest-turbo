<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Transaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use App\Form\TransactionType;
use App\Form\TransactionFilterType;
use App\Service\TransactionService;

class TransactionController extends AbstractController
{

    private $transactionService;

    public function __construct(TransactionService $transactionService) {
        $this->transactionService = $transactionService;
    }

    #[Route('/transactions', name: 'transaction_list')]
    public function transactionList(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory, CsrfTokenManagerInterface $csrfTokenManager): Response
    {

        $data = [
            'category' => $request->get('category') !== null ? intval($request->get('category')) : 1,
            'page' => $request->get('page') !== null ? intval($request->get('page')) : 1,
            'orderId' => $request->get('orderId') !== null ? intval($request->get('orderId')) : null,
            'orderDate' => $request->get('orderDate') !== null ? intval($request->get('orderDate')) : null,
            'orderLastname' => $request->get('orderLastname') !== null ? intval($request->get('orderLastname')) : null,
            'orderAmount' => $request->get('orderAmount') !== null ? intval($request->get('orderAmount')) : null,
            'order' => $request->get('order') !== null ? $request->get('order') : 'ASC'
        ];

        /******************************* */
        
        $investment = new Transaction();
        $form = $formFactory->create(TransactionType::class, $investment);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        $token = $request->request->get('_csrf_token_transaction_user');

        if($form->isSubmitted()){
            if($csrfTokenManager->isTokenValid(new CsrfToken('user_investment_form', $token))){

                $user = $investment->getUser();

                $balance = $this->transactionService->calculateBalance($user);

                if($balance < floatval($investment->getAmount())){
                    $this->addFlash('error', "L'utilisateur n'a pas assez d'argent pour investir ce montant. Il lui reste ". $balance . " € sur son solde.");
                    return $this->redirectToRoute('transaction_list');
                }

                $investment->setDate(new \DateTimeImmutable());
                $investment->setCategory(1);
                $em->persist($investment);
                $em->flush();

                $this->addFlash('success', "investissement crée avec succès.");
                return $this->redirectToRoute('transaction_list');
            }else{
                $this->addFlash('error', "Le jeton CSRF n'est pas valide.");
            }
        }

        /******************************* */

        $transactionFilterForm = $formFactory->create(TransactionFilterType::class);
        $transactionFilterForm->handleRequest($request);

        if ($transactionFilterForm->isSubmitted() && $transactionFilterForm->isValid()) {
            $data = [...$data, ...$transactionFilterForm->getData()];
        }

        $transactions = $em->getRepository(Transaction::class)->findTransactionsByFilters($data);
        $nbTransactionsFiltered = $em->getRepository(Transaction::class)->findTransactionsByFilters($data, true);
        $nbPages = max(ceil($nbTransactionsFiltered / Transaction::MAXPERPAGE), 1);

        return $this->render('transaction/transaction-list.html.twig', [
            'route_pagination' => 'transaction_list',
            'nbPages' => $nbPages,
            'actualPage' => $data['page'],
            'category' => $data['category'],
            'form' => $form,
            'transactions' => $transactions,
            'transactionFilterForm' => $transactionFilterForm
        ]);
    }
}
