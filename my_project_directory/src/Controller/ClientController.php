<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/clients')]
class ClientController extends AbstractController
{
    #[Route('', name: 'client_index', methods: ['GET'])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $clients = $doctrine->getRepository(Client::class)->findAll();
        return $this->render('client/index.html.twig', compact('clients'));
    }

    #[Route('/new', name: 'client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $client = new Client();
        $form = $this->createFormBuilder($client)
            ->add('name')
            ->add('phone')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $em->persist($client);
            $em->flush();
            return $this->redirectToRoute('client_index');
        }

        return $this->render('client/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, ManagerRegistry $doctrine): Response
    {
        $form = $this->createFormBuilder($client)
            ->add('name')
            ->add('phone')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('client_index');
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'client_delete_api', methods: ['DELETE'])]
    public function deleteApi(Client $client, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $em->remove($client);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'client_update_api', methods: ['PUT'])]
    public function updateApi(Request $request, Client $client, ManagerRegistry $doctrine): Response
    {
        $data = json_decode($request->getContent(), true);
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;

        if (!$field || !in_array($field, ['name', 'phone'], true)) {
            return new Response('Недопустимое поле', Response::HTTP_BAD_REQUEST);
        }

        $client->$field = $value;
        $doctrine->getManager()->flush();

        return new Response('Успешно обновлено', Response::HTTP_OK);
    }
}