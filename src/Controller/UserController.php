<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Image;
use App\Entity\Transaction;
use App\Entity\BankAccount;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use App\Form\TransactionType;
use App\Form\BankAccountType;
use App\Form\UserFilterType;
use App\Service\TransactionService;

class UserController extends AbstractController
{

    private $transactionService;

    public function __construct(TransactionService $transactionService) {
        $this->transactionService = $transactionService;
    }

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('user_list');
    }

    #[Route('/users', name: 'user_list')]
    public function userList(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory): Response
    {

        $data = [
            'page' => $request->get('page') !== null ? intval($request->get('page')) : 1,
            'orderId' => $request->get('orderId') !== null ? intval($request->get('orderId')) : null,
            'orderFirstname' => $request->get('orderFirstname') !== null ? intval($request->get('orderFirstname')) : null,
            'orderLastname' => $request->get('orderLastname') !== null ? intval($request->get('orderLastname')) : null,
            'orderEmail' => $request->get('orderEmail') !== null ? intval($request->get('orderEmail')) : null,
            'orderPhone' => $request->get('orderPhone') !== null ? intval($request->get('orderPhone')) : null,
            'order' => $request->get('order') !== null ? $request->get('order') : 'ASC'
        ];

        $userFilterForm = $formFactory->create(UserFilterType::class);
        $userFilterForm->handleRequest($request);

        if ($userFilterForm->isSubmitted() && $userFilterForm->isValid()) {
            $data = [...$data, ...$userFilterForm->getData()];
        }

        $users = $em->getRepository(User::class)->findUsersByFilters($data);
        $nbUsersFiltered = $em->getRepository(User::class)->findUsersByFilters($data, true);
        $nbPages = max(ceil($nbUsersFiltered / User::MAXPERPAGE), 1);
        
        return $this->render('user/user-list.html.twig', [           
            'route_pagination' => 'user_list',
            'nbPages' => $nbPages,
            'actualPage' => $data['page'],
            'users' => $users,
            'userFilterForm' => $userFilterForm
        ]);
    }

    #[Route('/users/{id}', name: 'user_item')]
    public function user(EntityManagerInterface $em, int $id, Request $request, FormFactoryInterface $formFactory, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw new \Exception('Utilisateur non trouvé.');
            // throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $bankAccounts = $em->getRepository(BankAccount::class)->findByUser($user);

        $balance = $this->transactionService->calculateBalance($user);

        $deposit = new Transaction();
        $form = $formFactory->create(TransactionType::class, $deposit);
        
        $form->handleRequest($request);

        $token = $request->request->get('_csrf_token_transaction_user');

        if($form->isSubmitted() && $request->request->get('form_deposit') !== null){
            if($csrfTokenManager->isTokenValid(new CsrfToken('user_deposit_form', $token))){
                $deposit->setDate(new \DateTimeImmutable());
                $deposit->setCategory(2);
                $deposit->setUser($user);
                $em->persist($deposit);
                $em->flush();

                $this->addFlash('success', "Dépôt effectué avec succès.");
                return $this->redirectToRoute('user_item', ['id' => $user->getId()]);
            }else{
                $this->addFlash('error', "Erreur lors de la création du dépôt.");
            }
        }

    ////////////////////////////

        $bankAccount = new BankAccount();
        $bankAccountForm = $formFactory->create(BankAccountType::class, $bankAccount);

        $bankAccountForm->handleRequest($request);

        $token = $request->request->get('_csrf_token_bank_account_user');

        if($bankAccountForm->isSubmitted()){
            if($csrfTokenManager->isTokenValid(new CsrfToken('user_bank_account_form', $token))){
                $bankAccount->setUser($user);
                $em->persist($bankAccount);
                $em->flush();

                $this->addFlash('success', "Compte bancaire ajouté avec succès.");
                return $this->redirectToRoute('user_item', ['id' => $user->getId()]);
            }else{
                $this->addFlash('error', "Erreur lors de l'ajout du compte bancaire.");
            }
        }

    ////////////////////////////

        $withdraw = new Transaction();
        $withdrawForm = $formFactory->create(TransactionType::class, $withdraw);

        $withdrawForm->handleRequest($request);

        $token = $request->request->get('_csrf_token_transaction_user');

        if($withdrawForm->isSubmitted() && $request->request->get('form_withdraw') !== null){
            if($csrfTokenManager->isTokenValid(new CsrfToken('user_withdraw_form', $token))){

                if($balance < $withdraw->getAmount()){
                    $this->addFlash('error', "L'utilisateur n'a pas assez d'argent pour effectuer ce retrait. Il lui reste ". $balance ." € sur son solde.");
                    return $this->redirectToRoute('user_item', ['id' => $user->getId()]);
                }

                $withdraw->setDate(new \DateTimeImmutable());
                $withdraw->setCategory(3);
                $withdraw->setUser($user);
                $em->persist($withdraw);
                $em->flush();

                $this->addFlash('success', "Retrait effectué avec succès.");
                return $this->redirectToRoute('user_item', ['id' => $user->getId()]);
            }else{
                $this->addFlash('error', "Erreur lors de la création du retrait.");
            }
        }
        
        ////////////////////////////

        $labels = [];
        $investmentData = [];
        $dividendsData = [];
        
        $sixMonthsAgo = (new \DateTime())->sub(new \DateInterval('P5M'));
        $currentDate = new \DateTime();

        $dateStart = $sixMonthsAgo->format('Y-m-d');
        $dateEnd = $currentDate->format('Y-m-d');

        if($request->request->get('graph_transactions_user') !== null){
                $dateStart = $request->request->get('date-start');
                $dateEnd = $request->request->get('date-end');
        }

        // Exemples de données des transactions financières
        $investmentData = $em->getRepository(Transaction::class)->findUserTransactionsByDate($user, $dateStart, $dateEnd, 1);
        $dividendsData = $em->getRepository(Transaction::class)->findUserTransactionsByDate($user, $dateStart, $dateEnd, 4);

        $start = new \DateTime($dateStart);
        $end = new \DateTime($dateEnd);
        $interval = new \DateInterval('P1M'); // Période d'un mois
        $dateRange = new \DatePeriod($start, $interval, $end);
    
        foreach ($dateRange as $date) {
            $labels[] = $date->format('F'); // Format 'Jan 2024'
        }
    
        // Ajoute le mois final si non inclus
        if ($end->format('F') !== $labels[count($labels) - 1]) {
            $labels[] = $end->format('F');
        }

        return $this->render('user/user-item.html.twig', [
            'user' => $user,
            'bankAccounts' => $bankAccounts,
            'users_images_path' => $this->getParameter('users_images_path'),
            'form' => $form,
            'bankAccountForm' => $bankAccountForm,
            'withdrawForm' => $withdrawForm,
            'balance' => $balance,
            'chart_labels' => $labels,
            'investment_data' => $investmentData,
            'dividends_data' => $dividendsData,
        ]);
    }

    #[Route('/users/update/{id}', name: 'user_edit')]
    public function updateUser(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory, int $id, UserPasswordHasherInterface $userPasswordEncoder, SluggerInterface $slugger): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw new NotFoundHttpException('L\'utilisateur demandé n\'existe pas.');
        }

        $form = $formFactory->create(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveUser($em, $user, $form, $userPasswordEncoder, $slugger, $id);
            return $this->redirectToRoute('user_item', ['id' => $id]);
        } elseif ($form->isSubmitted()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('user/user-form.html.twig', [
            'page' => 'update',
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    #[Route('/users/delete/{id}', name: 'user_delete')]
    public function deleteUser(Request $request, EntityManagerInterface $em, int $id, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'Erreur lors de la suppression de l\'utilisateur.');
        } else {
            $token = $request->request->get('_token');
            if ($csrfTokenManager->isTokenValid(new CsrfToken('delete_user_' . $user->getId(), $token))) {
                $em->remove($user);
                $em->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Jeton CSRF invalide.');
            }
        }

        return $this->redirectToRoute('user_list');
    }

    #[Route('/bank-account/delete/{id}', name: 'bank_account_delete')]
    public function deleteBankAccount(Request $request, EntityManagerInterface $em, int $id, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $bankAccount = $em->getRepository(BankAccount::class)->find($id);

        if (!$bankAccount) {
            $this->addFlash('error', 'Erreur lors de la suppression du compte bancaire.');
        } else {
            $token = $request->request->get('_bank_account_token');
            if ($csrfTokenManager->isTokenValid(new CsrfToken('delete_bank_account_' . $bankAccount->getId(), $token))) {
                $em->remove($bankAccount);
                $em->flush();
                $this->addFlash('success', 'Compte bancaire supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Jeton CSRF invalide.');
            }
        }

        return $this->redirectToRoute('user_item', ['id' => $bankAccount->getUser()->getId()]);
    }

    #[Route('/user/create', name: 'user_add')]
    public function createUser(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory, UserPasswordHasherInterface $userPasswordEncoder, SluggerInterface $slugger): Response
    {
        $user = new User();
        $form = $formFactory->create(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($form->get('password')->getData())) {
                $this->addFlash('error', 'Veuillez saisir un mot de passe pour l\'utilisateur.');
            } else {
                $this->saveUser($em, $user, $form, $userPasswordEncoder, $slugger);
                return $this->redirectToRoute('user_list');                    
            }
        }

        return $this->render('user/user-form.html.twig', [
            'page' => 'add',
            'form' => $form->createView()
        ]);
    }

    private function saveUser(EntityManagerInterface $em, User $user, $form, UserPasswordHasherInterface $userPasswordEncoder, SluggerInterface $slugger, int $id = null): void
    {
        if (!empty($form->get('password')->getData())) {
            $encodedPassword = $userPasswordEncoder->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($encodedPassword);
        }

        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = $this->handleFileUpload($imageFile, $slugger);
            if ($newFilename) {
                $image = new Image();
                $image->setPath($newFilename);
                $user->setImage($image);
            }
        }

        if (!$id) {
            $user->setCreateDate(new \DateTimeImmutable());
        }

        $user->setRole('ROLE_USER');
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Les modifications ont été enregistrées avec succès.');
    }

    private function handleFileUpload(UploadedFile $file, SluggerInterface $slugger): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->getParameter('users_images_directory'), $newFilename);
            return $newFilename;
        } catch (FileException $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
            return null;
        }
    }
}