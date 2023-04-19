<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        /**
         * Étapes :
         * Création du formulaire
         * Vérification et sauvegarde
         * Récupération de la table
         */
        $category = new Category(); // Création d'un objet vide pour le formulaire
        // Appel du formulaire CategoryType en lui envoyant l'objet vide :
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request); // Vérification de la requête
        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $em->persist($category); // Préparation de l'insertion
            $em->flush(); // Exécution de l'insertion
            $this->addFlash('success', 'La catégorie a bien été ajoutée !');
            return $this->redirectToRoute('app_category');
        }

        /**
         * Import de la classe Category grâce au getRepository
         * findAll() permet de récupérer toutes les catégories
         */
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('category/index.html.twig', [
            // 'controller_name' => 'CategoryController',
            'categories' => $categories,
            'form' => $form->createView(), // Création de la vue du formulaire
        ]);
    }

    #[Route('/category/{id}', name: 'app_category_show')]
    public function category(Category $category = null, Request $request, EntityManagerInterface $em): Response
    {
        /**
         * Symfony va automatiquement chercher la catégorie grâce à l'id (paramConverter)
         */
        if ($category == null) {
            $this->addFlash('danger', 'La catégorie n\'existe pas !');
            return $this->redirectToRoute('app_category');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request); // Vérification de la requête
        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $em->persist($category); // Préparation de l'insertion
            $em->flush(); // Exécution de l'insertion
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'edit' => $form->createView(),
        ]);
    }

    #[Route('/category/delete/{id}', name: 'app_category_delete')]
    public function delete(Category $category = null, EntityManagerInterface $em): Response
    {
        if ($category == null) {
            $this->addFlash('danger', 'La catégorie n\'existe pas !');
        } else {
            $em->remove($category);
            $em->flush();
            $this->addFlash('warning', 'La catégorie a bien été supprimée !');
        }
        return $this->redirectToRoute('app_category');
    }
}
