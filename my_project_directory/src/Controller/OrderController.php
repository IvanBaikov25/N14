<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_index', methods: ['GET'])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $orders = $doctrine->getRepository(Order::class)->findAll();
        return $this->render('order/index.html.twig', compact('orders'));
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $order = new Order();
        $form = $this->createFormBuilder($order)
            ->add('client')
            ->add('dishes', null, [
                'expanded' => true,
                'multiple' => true,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $em->persist($order);
            $em->flush();
            return $this->redirectToRoute('order_index');
        }

        return $this->render('order/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'PUT'])]
public function edit(Request $request, Order $order, ManagerRegistry $doctrine): Response
{
    $form = $this->createFormBuilder($order, [
        'method' => 'PUT',
    ])
        ->add('client')
        ->add('dishes', null, [
            'expanded' => true,
            'multiple' => true,
        ])
        ->getForm();

    if ($request->isMethod('PUT')) {
        $data = $request->request->all();
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('order_index');
        }
    }

    return $this->render('order/edit.html.twig', [
        'order' => $order,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'order_delete_api', methods: ['DELETE'])]
    public function deleteApi(Order $order, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $em->remove($order);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

}