<?php

namespace App\Controller;

use App\Entity\Dish;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dishes')]
class DishController extends AbstractController
{
    #[Route('/', name: 'dish_index', methods: ['GET'])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $dishes = $doctrine->getRepository(Dish::class)->findAll();
        return $this->render('dish/index.html.twig', compact('dishes'));
    }

    #[Route('/new', name: 'dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $dish = new Dish();
        $form = $this->createFormBuilder($dish)
            ->add('name')
            ->add('price')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($dish);
            $entityManager->flush();
            return $this->redirectToRoute('dish_index');
        }

        return $this->render('dish/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'dish_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, ManagerRegistry $doctrine): Response
    {
        $form = $this->createFormBuilder($dish)
            ->add('name')
            ->add('price')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('dish_index');
        }

        return $this->render('dish/edit.html.twig', [
            'dish' => $dish,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dish->getId(), $request->request->get('_token'))) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($dish);
            $entityManager->flush();
        }
        return $this->redirectToRoute('dish_index');
    }
}