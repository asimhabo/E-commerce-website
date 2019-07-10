<?php

namespace App\Controller\Admin;
use App\Repository\OrdersRepository;
use App\Repository\OrderDetailRepository;
use App\Entity\Orders;
use App\Repository\ShopcartRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\Admin\Response;
use App\Entity\OrderDetail;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends Controller
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index()
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
    
    /**
     * @Route("/admin/orders/{slug}", name="admin_orders_index")
     */
    public function orders($slug , OrdersRepository $orderRepository)
    {
        $orders = $orderRepository->findBy(['status' => $slug]);

        return $this->render('admin/orders/index.html.twig' , ['orders' => $orders]);

    }

     /**
     * @Route("/admin/orders/show/{id}", name="admin_orders_show" , methods="GET")
     */
    public function show($id , Orders $orders , OrderDetailRepository $orderDetailRepository) 
    {
        $orderdetail=$orderDetailRepository->findBy(['orderid' => $id]);

        return $this->render('admin/orders/show.html.twig' , ['order' => $orders, 'orderdetail' => $orderdetail,]);

    }
/**
     * @Route("/admin/orders/{id}/update", name="admin_orders_update", methods="POST")
     */
    public function update($id, Request $request, Orders $orders)
    {

        $em = $this->getDoctrine()->getManager();
        $sql="UPDATE orders SET shipinfo=:shipinfo, status=:status, note=:note WHERE id=:id";
        $statement=$em->getConnection()->prepare($sql);
        $statement->bindValue('shipinfo', $request->request->get('shipinfo'));
        $statement->bindValue('note', $request->request->get('note'));
        $statement->bindValue('status', $request->request->get('status'));
        $statement->bindValue('id', $id);
        $statement->execute();
        $this->addFlash('success','Order information is updated successfully');

        return $this->redirectToRoute('admin_orders_show', array('id' => $id));
    }    
}
