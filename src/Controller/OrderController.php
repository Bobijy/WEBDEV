<?php

namespace App\Controller;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/order/{id}', name: 'app_order_show', requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Ownership guard
        if ($order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }
    #[Route('/order/{id}/receipt', name: 'app_order_receipt', requirements: ['id' => '\d+'])]
    public function receipt(Order $order): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        if ($order->getStatus() !== 'Completed') {
            $this->addFlash('error', 'Receipts are only available for completed orders.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/receipt.html.twig', [
            'order' => $order,
        ]);
    }
}
