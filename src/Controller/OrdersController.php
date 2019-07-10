<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrderDetail;
use App\Form\OrdersType;
use App\Entity\Admin\Product;
use App\Repository\ShopcartRepository;
use App\Repository\OrdersRepository;
use App\Repository\OrderDetailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/orders")
 */
class OrdersController extends Controller
{
    /**
     * @Route("/", name="orders_index", methods="GET")
     */
    public function index(OrdersRepository $ordersRepository): Response
    {
        $user = $this->getUser(); //Calling login user data
        $userid=$user->getid();
        return $this->render('orders/index.html.twig', ['orders' => $ordersRepository->findBy(['userid' => $userid])]);
    }

    /**
     * @Route("/new", name="orders_new", methods="GET|POST")
     */
    public function new(Request $request, ShopcartRepository $shopcartRepository): Response
    {
        $orders = new Orders();
        $form = $this->createForm(OrdersType::class, $orders);
        $form->handleRequest($request);

        $user = $this->getUser(); //Calling login user data
        $userid = $user->getid();
        $total = $shopcartRepository->getUserShopCartTotal($userid); //get total amount of user shopcart
        //dump($total);
        //die();
        //buradan üstü sadece formu göstermek için

        $submittedToken = $request->request->get('token'); // get csrf token information
        if($this->isCsrfTokenValid('form-order' , $submittedToken)) {
            if ($form->isSubmitted()) {

                // kredi karti bilgilerini ilgili banka servisine gonder
                //onay gelirse kaydetmeye devam et yoksa order sayfasina hata gonder
                //shopcart tan orderlar orderdetails e gidiyor daha sonra 2. olarak shopcart siliniyor
                $em = $this->getDoctrine()->getManager();

                $orders->setUserid($userid);
                $orders->setAmount($total);
                $orders->setStatus("New");

                $em->persist($orders);
                $em->flush();

                $orderid = $orders->getId(); // Get last orders data id  
                
                $shopcart = $shopcartRepository->getUserShopCart($userid);
                //dump($shopcart);
                //die();

                foreach($shopcart as $item){
                    //echo "<br>". $item["title"]."- ". $item["sprice"]."*".$item["quantity"]."=". $item["total"];

                    $orderdetail = new OrderDetail();
                    //Filling order details  data from shopcart
                    $orderdetail->setOrderid($orderid);
                    $orderdetail->setUserid($user->getid()); //login user id
                    $orderdetail->setProductid($item["productid"]);
                    $orderdetail->setPrice($item["sprice"]);
                    $orderdetail->setQuantity($item["quantity"]);
                    $orderdetail->setAmount($item["total"]);
                    $orderdetail->setName($item["title"]);
                    $orderdetail->setStatus("Ordered");

                    $em->persist($orderdetail);
                    $em->flush();
                }
            
                //delete user shopcart products
                $em = $this->getDoctrine()->getManager();
                $query = $em->createQuery('
                    DELETE FROM App\Entity\Shopcart s WHERE s.userid=:userid
                    ')
                ->setParameter('userid', $userid);
                $query->execute();

                $this->addFlash('success', 'Your order has been successfully executed  Thank you!');
                return $this->redirectToRoute('orders_index');
            }    
        }

        return $this->render('orders/new.html.twig', [
            'order' => $orders,
            'total' => $total,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="orders_show", methods="GET")
     */
    public function show(Orders $order, OrderDetailRepository $orderDetailRepository): Response
    {
        $user = $this->getUser(); //Calling login user data
        $userid = $user->getid();
        $orderid = $order->getid();

        $orderdetail = $orderDetailRepository->findBy(['orderid' => $orderid]);
        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderdetail' => $orderdetail,
            ]);
    }

    /**
     * @Route("/{id}/edit", name="orders_edit", methods="GET|POST")
     */
    public function edit(Request $request, Orders $order): Response
    {
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('orders_edit', ['id' => $order->getId()]);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="orders_delete", methods="DELETE")
     */
    public function delete(Request $request, Orders $order): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders_index');
    }
}
