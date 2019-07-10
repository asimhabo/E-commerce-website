<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\Admin\SettingRepository;
use App\Repository\Admin\ProductRepository;
use App\Entity\Admin\Product;
use App\Entity\User;
use App\Entity\Admin\Messages;
use App\Form\Admin\MessagesType;
use App\Entity\Admin\Setting;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Comments;
use App\Repository\Admin\ImageRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route("/home", name="home")
     */
    public function index(SettingRepository $settingRepository ,CategoryRepository $categoryRepository )
    {
        $data = $settingRepository->findAll();
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM product  ORDER BY ID DESC LIMIT 5";
        $statement = $em->getConnection()->prepare($sql);
       // $statement->bindValue('pid',$parent);
        $statement->execute();
        $sliders= $statement->fetchAll();
        $products=$this->getDoctrine()->getRepository(Product::class)->findRand(6);
        
        $cats = $this->categorytree();

        $cats[0]= '<ul id="menu-v">';

        return $this->render('home/index.html.twig', [
            'data' => $data,
            'cats' => $cats,
            "products"=>$products,
            'sliders' => $sliders,
        ]);
    }

  
    /**
     * @Route("/aboutus", name="aboutus")
     */
    public function aboutus(SettingRepository $settingRepository)
    {
        $data = $settingRepository->findAll();

        return $this->render('home/aboutus.html.twig', [
            'data' => $data,
        ]);
    }

    /**
     * @Route("/referances", name="referances")
     */
    public function referances(SettingRepository $settingRepository)
    {
        $data = $settingRepository->findAll();

        return $this->render('home/referances.html.twig', [
            'data' => $data,
        ]);
    }

    public function categorytree($parent=0, $user_tree_array=''){
        if (!is_array($user_tree_array))
        $user_tree_array= array();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM category WHERE status='True' AND parentid=".$parent;
        $statement = $em->getConnection()->prepare($sql);
       // $statement->bindValue('pid',$parent);
        $statement->execute();
        $result= $statement->fetchAll();

        if (count($result)>0){
            $user_tree_array[] = "<ul>";
            foreach ($result as $row){
                $user_tree_array[] = "<li> <a href='/category/".$row['id']."'>".$row['title']."</a>";
                $user_tree_array = $this->categorytree($row['id'], $user_tree_array);
            }
            $user_tree_array[] = "</li></ul>";


        }
        return $user_tree_array;


    }

      /**
     * @Route("/category/{catid}/", name="category_products", methods="GET")
     */
    public function CategoryProducts($catid,CategoryRepository $categoryRepository)
    {
    $data=$categoryRepository->findBy(['id'=> $catid]);
    $cats = $this->categorytree();
    $cats[0]= '<ul id="menu-v">';
    
     $em = $this->getDoctrine()->getManager();
     $sql = 'SELECT * FROM product WHERE status="True" AND category_id=:catid';
     $statement = $em->getConnection()->prepare($sql);
     $statement->bindValue('catid',$catid);
     $statement->execute();
     $products= $statement->fetchAll();

     return $this->render('home/products.html.twig', [
        'data' => $data,
        'products' => $products,
        'cats' => $cats,
    ]);
    }

      /**
     * @Route("/product/{id}/", name="product_detail", methods={"GET","POST"})
     */
    public function ProductDetail($id,ProductRepository $productRepository,ImageRepository $imageRepository)
    {
        $data=$productRepository->findBy(['id' => $id]);
        $images=$imageRepository->findBy(['product_id' => $id]);
        $comments=$this->getDoctrine()->getRepository(Comments::class)->getwithuser($id);

        $cats=$this->categorytree();
        $cats[0]= '<ul id="menu-v">';

        return $this->render('home/product_detail.html.twig', [
            'data' => $data,
            'images' => $images,
            'cats' => $cats,
            'comments'=>$comments,
        ]);
    }
   /**
     * @Route("/comment/add/{pid}/", name="comment_add",  methods="GET")
     */
    public function commentadd($pid, Request $request)
    {
        if($this->isGranted('ROLE_USER') || $this->isGranted('ROLE_ADMIN')){
        $user= $this->getUser();
       $comment = new Comments();
       $comment->setUserid($user->getId());
       $comment->setProductid($pid);
       $comment->setComment($request->get('comment'));
       $comment->setComdate(date('Y/m/d H:m:sa'));
       $em = $this->getDoctrine()->getManager();
       $em->persist($comment);
       $em->flush();

        }
        
     return $this->redirect('/product/'.$pid);

    }

     /**
     * @Route("/comment/delete/{pid}/{id}", name="comment_delete",  methods="GET")
     */
    public function commentdelete($pid, Comments $comment)
    {
        if($this->isGranted('ROLE_USER') || $this->isGranted('ROLE_ADMIN')){
        $user= $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $em->remove($comment);
        $em->flush();

        }
        
     return $this->redirect('/product/'.$pid);

    }
    

/**
     * @Route("/register" , name="register")
     * Method("POST","GET")
     */
    public function register(Request $request){
        $msg="";
        $erorr="";
        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('signup', $submittedToken)) {
            $user=$this->getDoctrine()->getRepository(User::class)->findby(["email"=>$request->get("email")]);
            if($user==null){
                $user=new User();
                $user->setName($request->get("name"));
                $user->setEmail($request->get("email"));
                $user->setPassword($request->get("password"));
                $user->setAddress($request->get("address"));
                $user->setPhone($request->get("phone"));
                $user->setRoles("ROLE_USER");
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                $msg="your account has been created sucessfully";
            }
            else{
                $erorr="This email is already registerd before !";
            }
        }
        return $this->render("security/login.html.twig",["msg"=>$msg,"erorr"=>$erorr]);
    }
    /**
     * @Route("/userpanel/show", name="userpanel_show")
     * Method("POST","GET")
     */
    public function account(Request $request){
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $msg="";
        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('account', $submittedToken)) {
            $user=$this->getDoctrine()->getRepository(User::class)->find($user->getId());
            $user->setName($request->get("name"));
            $user->setEmail($request->get("email"));
            $user->setAddress($request->get("address"));
            $user->setPhone($request->get("phone"));
            if($request->get("newpass")!=""){
                $user->setPassword($request->get("newpass"));
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            $msg="Updated Successfuly";
        }
        return $this->render("userpanel/show.html.twig",["msg"=>$msg]);
    }
    /**
     * @Route("/contact", name="contact")
     * Method("POST","GET")
     */
    public function contact(Request $request)
    {
        $setting=$this->getDoctrine()->getRepository(Setting::class)->findAll();
        $setting=$setting[0];
        $submittedToken = $request->request->get('token');
        $msg="";
        if ($this->isCsrfTokenValid('message', $submittedToken)) {
            $message=new Messages();
            $message->setName($request->get("name"));
            $message->setEmail($request->get("email"));
            $message->setSubject($request->get("subject"));
            $message->setMessage($request->get("message"));
            $message->setMessagedate(date("Y-m-d h:i:sa"));
            $message->setStatus("unreaded");
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($message);
            $entityManager->flush();
            $msg="your message has been recived sucessfully";
        }
        return $this->render('home/contact.html.twig',["setting"=>$setting,"msg"=>$msg]);
    }
}
