<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Project;
use App\Entity\Transaction;
use Symfony\Component\Form\FormFactoryInterface;
use App\Form\ProjectType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Image;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use App\Form\ProjectFilterType;

class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'project_list')]
    public function projectList(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory): Response
    {

        $data = [
            'page' => $request->get('page') !== null ? intval($request->get('page')) : 1,
            'orderId' => $request->get('orderId') !== null ? intval($request->get('orderId')) : null,
            'orderName' => $request->get('orderName') !== null ? intval($request->get('orderName')) : null,
            'orderAge' => $request->get('orderAge') !== null ? intval($request->get('orderAge')) : null,
            'orderYield' => $request->get('orderYield') !== null ? intval($request->get('orderYield')) : null,
            'orderRisk' => $request->get('orderRisk') !== null ? intval($request->get('orderRisk')) : null,
            'order' => $request->get('order') !== null ? $request->get('order') : 'ASC'
        ];

        $projectFilterForm = $formFactory->create(projectFilterType::class);
        $projectFilterForm->handleRequest($request);

        if($projectFilterForm->isSubmitted() && $projectFilterForm->isValid()) {
            $data = [...$data, ...$projectFilterForm->getData()];
        }

        $projects = $em->getRepository(Project::class)->findProjectsByFilters($data);
        $nbProjectsFiltered = $em->getRepository(Project::class)->findProjectsByFilters($data, true);
        $nbPages = max(ceil($nbProjectsFiltered / Project::MAXPERPAGE), 1);

        return $this->render('project/project-list.html.twig', [            
            'route_pagination' => 'project_list',
            'nbPages' => $nbPages,
            'actualPage' => $data['page'],
            'projects' => $projects,
            'risks' => Project::RISKS,
            'projectFilterForm' => $projectFilterForm
        ]);

    }

    #[Route('/projects/{id}', name: 'project_item')]
    public function projectItem(EntityManagerInterface $em, $id, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {

        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            throw new \Exception('Projet non trouvé.');
            // throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $labels = [];
        $currentMonth = new \DateTime();

        for ($i = 5; $i >= 0; $i--) {
            $month = $currentMonth->modify("-$i month")->format('F');
            $labels[] = $month;
            $currentMonth = new \DateTime(); // Réinitialiser la date pour le prochain itération
        }

        // Exemples de données des transactions financières
        $investmentData = $em->getRepository(Transaction::class)->findLastTransactionsByProject($project, 1);
        $dividendsData = $em->getRepository(Transaction::class)->findLastTransactionsByProject($project, 4);

        /****************************************** */
        $token = $request->request->get('_token');
        if($request->request->get('amount') !== null) {

            if($csrfTokenManager->isTokenValid(new CsrfToken('dividend_' . $project->getId(), $token))){
                $amount = floatval($request->request->get('amount'));

                $investments = $em->getRepository(Transaction::class)->findBy(['project' => $project, 'category' => 1]);
                $totalAmountInvestments = floatval($em->getRepository(Transaction::class)->getTotalAmountProjectInvestments($project));

                foreach($investments as $inv){

                    $invAmount = floatval($inv->getAmount());
                    $percentage = ($invAmount / $totalAmountInvestments) * 100;
                    $amountDividend = number_format(($amount/100) * $percentage, 2, '.', '');
                
                    $user = $inv->getUser();

                    $dividend = new Transaction();
                    $dividend->setAmount($amountDividend);
                    $dividend->setProject($project);
                    $dividend->setInvestment($inv);
                    $dividend->setUser($user);
                    $dividend->setDate(new \DateTimeImmutable());
                    $dividend->setCategory(4);

                    $em->persist($dividend);
                }

                $em->flush();

                $this->addFlash('success', 'Dividendes versés avec succès.');
                return $this->redirectToRoute('project_item');
            }else{
                $this->addFlash('error', 'Jeton CSRF invalide.');
            }

        }

    /****************************************** */

        return $this->render('project/project-item.html.twig', [
            'categories' => Project::CATEGORIES,
            'risks' => Project::RISKS,
            'project' => $project,
            'projects_images_path' => $this->getParameter('projects_images_path'),
            'chart_labels' => $labels,
            'investment_data' => $investmentData,
            'dividends_data' => $dividendsData,
        ]);
    }

    #[Route('/project/create', name: 'project_add')]
    public function createProject(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory, SluggerInterface $slugger): Response
    {

        $project = new Project();

        $form = $formFactory->create(ProjectType::class, $project);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $project->setDate(new \DateTimeImmutable());
                $this->saveProject($em, $project, $form, $slugger);
                return $this->redirectToRoute('project_list');                    
            }
        }
        
        return $this->render('project/project-form.html.twig', [
            'page' => 'add',
            'form' => $form->createView()
        ]);

    }

    #[Route('/project/update/{id}', name: 'project_update')]
    public function updateProject(EntityManagerInterface $em, Request $request, FormFactoryInterface $formFactory, SluggerInterface $slugger, $id): Response
    {
        $project = $em->getRepository(Project::class)->find($id);
        
        if (!$project) {
            throw new NotFoundHttpException('L\'utilisateur demandé n\'existe pas.');
        }

        // Créer le formulaire pour le projet
        $form = $formFactory->create(ProjectType::class, $project);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->saveProject($em, $project, $form, $slugger, $id);
                return $this->redirectToRoute('project_item', ['id' => $id]);
            } else {
                // Ajouter un message d'erreur
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        // Rendre la vue Twig avec le formulaire et les données utilisateur
        return $this->render('project/project-form.html.twig', [
            'page' => 'update',
            'project' => $project,
            'form' => $form->createView()
        ]);

    }

    public function saveProject(EntityManagerInterface $em, Project $project, $form, SluggerInterface $slugger, int $id = null){

        // Gestion de l'upload de l'image
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('projects_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // Gestion des erreurs
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
            }

            $image = new Image();
            $image->setPath($newFilename);
            $project->setImage($image);
        }

        $em->persist($project);
        $em->flush();

        // Ajouter un message de succès
        $this->addFlash('success', 'Projet enregistrée avec succès.');

    }

}
