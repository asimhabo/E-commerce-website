<?php

namespace App\Controller\Userpanel;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
/**
     * @Route("/userpanel")
     */
class UserpanelController extends Controller
{
    /**
     * @Route("/", name="userpanel")
     */
    public function index()
    {
        return $this->redirectToRoute('userpanel_show');
    }

    
}
