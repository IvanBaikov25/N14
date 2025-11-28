<?php
namespace App\Controller;
use App\Entity\Dish;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dishes')]
class DishController extends AbstractController
{
    #[Route('/', name: 'dish_index')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $dishes = $doctrine->getRepository(Dish::class)->findAll();
        return $this->render('dish/index.html.twig', ['dishes' => $dishes]);
    }

    #[Route('/new', name: 'dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $dish = new Dish();
        $form = $this->createFormBuilder($dish)
            ->add('name')->add('price')->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $request->files->get('photo');
            if ($photo) {
                $this->savePhoto($dish, $photo, $slugger);
            }
            $doctrine->getManager()->persist($dish);
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('dish_index');
        }
        return $this->render('dish/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'dish_edit', methods: ['GET', 'PUT'])]
    public function edit(Request $request, Dish $dish, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $form = $this->createFormBuilder($dish, ['method' => 'PUT'])
            ->add('name')->add('price')->getForm();

        if ($request->isMethod('PUT')) {
            $form->submit($request->request->all());
            if ($form->isSubmitted() && $form->isValid()) {
                $delete = $request->request->get('delete_photo');
                if ($delete) {
                    $this->removePhoto($dish);
                } else {
                    $photo = $request->files->get('photo');
                    if ($photo) {
                        $this->savePhoto($dish, $photo, $slugger);
                    }
                }
                $doctrine->getManager()->flush();
                return $this->redirectToRoute('dish_index');
            }
        }
        return $this->render('dish/edit.html.twig', ['dish' => $dish, 'form' => $form->createView()]);
    }

    #[Route('/{id}/delete', name: 'dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dish->getId(), $request->request->get('_token'))) {
            $this->removePhoto($dish);
            $doctrine->getManager()->remove($dish);
            $doctrine->getManager()->flush();
        }
        return $this->redirectToRoute('dish_index');
    }

    private function savePhoto(Dish $dish, $file, SluggerInterface $slugger): void
    {
        $this->removePhoto($dish);
        $orig = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe = $slugger->slug($orig);
        $name = $safe . '-' . uniqid() . '.' . $file->guessExtension();
        $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/dishes', $name);
        $dish->setPhotoPath('/uploads/dishes/' . $name);
    }

    private function removePhoto(Dish $dish): void
    {
        if ($path = $dish->getPhotoPath()) {
            $full = $this->getParameter('kernel.project_dir') . $path;
            if (file_exists($full))
                unlink($full);
            $dish->setPhotoPath(null);
        }
    }
}