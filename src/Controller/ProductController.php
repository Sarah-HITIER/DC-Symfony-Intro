<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product')]
    public function index(EntityManagerInterface $em, Request $request, SluggerInterface $slugger, TranslatorInterface $translator): Response
    {
        /**
         * Étapes :
         * Création du formulaire
         * Vérification et sauvegarde
         * Récupération de la table
         */
        $product = new Product(); // Création d'un objet vide pour le formulaire
        // Appel du formulaire ProductType en lui envoyant l'objet vide :
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request); // Vérification de la requête
        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide

            $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($imageFile) {
                /** 
                 * Plus simple :
                 * $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                 */
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                /**
                 * FIN Plus simple
                 */
                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image !');
                }

                // updates the 'imageFilename' property to store the PDF file name
                // instead of its contents
                $product->setImage($newFilename);
            }

            $em->persist($product); // Préparation de l'insertion
            $em->flush(); // Exécution de l'insertion
            $this->addFlash('success', $translator->trans('product.added'));
            // return $this->redirectToRoute('app_product');
        }

        /**
         * Import de la classe Product grâce au getRepository
         * findAll() permet de récupérer toutes les produits
         */
        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', [
            // 'controller_name' => 'ProductController',
            'products' => $products,
            'add' => $form->createView(), // Création de la vue du formulaire
        ]);
    }

    #[Route('/{id}', name: 'app_product_show')]
    public function product(Product $product = null, Request $request, EntityManagerInterface $em): Response
    {
        /**
         * Symfony va automatiquement chercher la catégorie grâce à l'id (paramConverter)
         */
        if ($product == null) {
            $this->addFlash('danger', 'La catégorie n\'existe pas !');
            return $this->redirectToRoute('app_product');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request); // Vérification de la requête
        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            $em->persist($product); // Préparation de l'insertion
            $em->flush(); // Exécution de l'insertion
            $this->addFlash('success', 'Le produit a bien été modifié !');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'edit' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_product_delete')]
    public function delete(Product $product = null, EntityManagerInterface $em): Response
    {
        if ($product == null) {
            $this->addFlash('danger', 'Le produit n\'existe pas !');
            return $this->redirectToRoute('app_product');
        } else {
            $em->remove($product);
            $em->flush();
            $this->addFlash('warning', 'Le produit a bien été supprimé !');
        }

        return $this->redirectToRoute('app_product');
    }
}
