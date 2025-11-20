<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Dish;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->redirectToRoute('client_index');
    }

    #[Route('/clients', name: 'client_index', methods: ['GET'])]
    public function listClients(EntityManagerInterface $em): Response
    {
        $clients = $em->getRepository(Client::class)->findAll();
        return $this->render('admin.html.twig', [
            'tab' => 'clients',
            'clients' => $clients,
            'dishes' => [],
            'orders' => [],
        ]);
    }

    #[Route('/clients/new', name: 'client_new', methods: ['POST'])]
    public function newClient(Request $request, EntityManagerInterface $em): Response
    {
        $name = trim($request->request->get('name', ''));
        $phone = trim($request->request->get('phone', ''));

        if ($name && $phone) {
            $client = new Client();
            $client->setName($name);
            $client->setPhone($phone);
            $em->persist($client);
            $em->flush();
        }

        return $this->redirectToRoute('client_index');
    }

    #[Route('/dishes', name: 'dish_index', methods: ['GET'])]
    public function listDishes(EntityManagerInterface $em): Response
    {
        $dishes = $em->getRepository(Dish::class)->findAll();
        return $this->render('admin.html.twig', [
            'tab' => 'dishes',
            'clients' => [],
            'dishes' => $dishes,
            'orders' => [],
        ]);
    }

    #[Route('/dishes/new', name: 'dish_new', methods: ['POST'])]
    public function newDish(Request $request, EntityManagerInterface $em): Response
    {
        $name = trim($request->request->get('name', ''));
        $price = filter_var($request->request->get('price'), FILTER_VALIDATE_FLOAT);

        if ($name && $price > 0) {
            $dish = new Dish();
            $dish->setName($name);
            $dish->setPrice($price);
            $em->persist($dish);
            $em->flush();
        }

        return $this->redirectToRoute('dish_index');
    }

    #[Route('/orders', name: 'order_index', methods: ['GET'])]
    public function listOrders(EntityManagerInterface $em): Response
    {
        $orders = $em->getRepository(Order::class)->findBy([], ['id' => 'DESC']);
        return $this->render('admin.html.twig', [
            'tab' => 'orders',
            'clients' => [],
            'dishes' => [],
            'orders' => $orders,
        ]);
    }

    #[Route('/orders/new', name: 'order_new', methods: ['POST'])]
    public function newOrder(Request $request, EntityManagerInterface $em): Response
    {
        $clientId = $request->request->getInt('client');
        $dishIds = $request->request->all('dishes');

        if ($clientId && !empty($dishIds)) {
            $client = $em->getRepository(Client::class)->find($clientId);
            if ($client) {
                $order = new Order();
                $order->setClient($client);

                foreach ($dishIds as $dishId) {
                    $dish = $em->getRepository(Dish::class)->find($dishId);
                    if ($dish) {
                        $order->addDish($dish);
                    }
                }

                if (count($order->getDishes()) > 0) {
                    $em->persist($order);
                    $em->flush();
                }
            }
        }

        return $this->redirectToRoute('order_index');
    }
}