<?php
namespace App\Controller;
use App\Entity\Order;
use App\Entity\OrderFile;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/orders')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_index')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $orders = $doctrine->getRepository(Order::class)->findAll();
        return $this->render('order/index.html.twig', ['orders' => $orders]);
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $order = new Order();
        $form = $this->createFormBuilder($order)
            ->add('client')
            ->add('dishes', null, ['expanded' => true, 'multiple' => true])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->persist($order);
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('order_index');
        }
        return $this->render('order/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'PUT'])]
    public function edit(Request $request, Order $order, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $form = $this->createFormBuilder($order, ['method' => 'PUT'])
            ->add('client')
            ->add('dishes', null, ['expanded' => true, 'multiple' => true])
            ->getForm();

        if ($request->isMethod('PUT')) {
            $form->submit($request->request->all());
            if ($form->isSubmitted() && $form->isValid()) {
                // 🔹 Загрузка файлов
                $files = $request->files->get('files', []);
                if (!is_array($files))
                    $files = [$files];
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $orderFile = new OrderFile();
                        $orig = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safe = $slugger->slug($orig);
                        $name = $safe . '-' . uniqid() . '.' . $file->guessExtension();
                        $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/orders', $name);
                        $orderFile->setOriginalName($file->getClientOriginalName());
                        $orderFile->setStoredPath('/uploads/orders/' . $name);
                        $order->addFile($orderFile);
                    }
                }
                $doctrine->getManager()->flush();
                return $this->redirectToRoute('order_index');
            }
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            foreach ($order->getFiles() as $file) {
                $path = $this->getParameter('kernel.project_dir') . $file->getStoredPath();
                if (file_exists($path))
                    unlink($path);
            }
            $doctrine->getManager()->remove($order);
            $doctrine->getManager()->flush();
        }
        return $this->redirectToRoute('order_index');
    }

    // Скачать файл заказа
    #[Route('/files/{id}/download', name: 'order_file_download')]
    public function downloadFile(OrderFile $file): Response
    {
        $path = $this->getParameter('kernel.project_dir') . $file->getStoredPath();
        return new BinaryFileResponse($path, 200, [
            'Content-Disposition' => 'attachment; filename="' . $file->getOriginalName() . '"',
        ]);
    }

    // Удалить файл заказа
    #[Route('/files/{id}/delete', name: 'order_file_delete', methods: ['POST'])]
    public function deleteFile(Request $request, OrderFile $file, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete-file' . $file->getId(), $request->request->get('_token'))) {
            $path = $this->getParameter('kernel.project_dir') . $file->getStoredPath();
            if (file_exists($path))
                unlink($path);
            $doctrine->getManager()->remove($file);
            $doctrine->getManager()->flush();
        }
        return $this->redirectToRoute('order_edit', ['id' => $file->getOrder()->getId()]);
    }

    //ЭКСПОРТ ВСЕХ ЗАКАЗОВ В EXCEL
    #[Route('/export/excel', name: 'order_export_excel')]
    public function exportExcel(ManagerRegistry $doctrine): Response
    {
        $orders = $doctrine->getRepository(Order::class)->findAll();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Заказы');

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Клиент');
        $sheet->setCellValue('C1', 'Блюда');
        $sheet->setCellValue('D1', 'Дата');

        $row = 2;
        foreach ($orders as $order) {
            $dishes = implode(', ', $order->getDishes()->map(fn($d) => $d->getName())->toArray());
            $sheet->setCellValue('A' . $row, $order->getId());
            $sheet->setCellValue('B' . $row, $order->getClient()->getName());
            $sheet->setCellValue('C' . $row, $dishes);
            $sheet->setCellValue('D' . $row, $order->getCreatedAt()->format('d.m.Y H:i'));
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(15);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'orders_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = sys_get_temp_dir() . '/' . $filename;
        $writer->save($tempFile);

        return new BinaryFileResponse($tempFile, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], true, null, true);
    }
}