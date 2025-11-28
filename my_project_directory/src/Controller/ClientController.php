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
    #[Route('/', name: 'client_index')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $clients = $doctrine->getRepository(Client::class)->findAll();
        return $this->render('client/index.html.twig', ['clients' => $clients]);
    }

    #[Route('/new', name: 'client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $client = new Client();
        $form = $this->createFormBuilder($client)
            ->add('name')->add('phone')->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->persist($client);
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('client_index');
        }
        return $this->render('client/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'client_edit', methods: ['GET', 'PUT'])]
    public function edit(Request $request, Client $client, ManagerRegistry $doctrine): Response
    {
        $form = $this->createFormBuilder($client, ['method' => 'PUT'])
            ->add('name')->add('phone')->getForm();

        if ($request->isMethod('PUT')) {
            $form->submit($request->request->all());
            if ($form->isSubmitted() && $form->isValid()) {
                $doctrine->getManager()->flush();
                return $this->redirectToRoute('client_index');
            }
        }
        return $this->render('client/edit.html.twig', ['client' => $client, 'form' => $form->createView()]);
    }

    #[Route('/{id}/delete', name: 'client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {
            $doctrine->getManager()->remove($client);
            $doctrine->getManager()->flush();
        }
        return $this->redirectToRoute('client_index');
    }
}