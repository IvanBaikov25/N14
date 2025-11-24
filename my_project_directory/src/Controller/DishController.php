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
    #[Route('', name: 'dish_index', methods: ['GET'])]
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
            $em = $doctrine->getManager();
            $em->persist($dish);
            $em->flush();
            return $this->redirectToRoute('dish_index');
        }

        return $this->render('dish/new.html.twig', ['form' => $form->createView()]);
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

    #[Route('/{id}', name: 'dish_delete_api', methods: ['DELETE'])]
    public function deleteApi(Dish $dish, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $em->remove($dish);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'dish_update_api', methods: ['PUT'])]
    public function updateApi(Request $request, Dish $dish, ManagerRegistry $doctrine): Response
    {
        $data = json_decode($request->getContent(), true);
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;

        if (!$field || !in_array($field, ['name', 'price'], true)) {
            return new Response('Недопустимое поле', Response::HTTP_BAD_REQUEST);
        }

        if ($field === 'price') {
            $value = (string) $value;
            if ((float) $value <= 0) {
                return new Response('Цена должна быть больше 0', Response::HTTP_BAD_REQUEST);
            }
        }

        $dish->$field = $value;
        $doctrine->getManager()->flush();

        return new Response('Успешно обновлено', Response::HTTP_OK);
    }
}