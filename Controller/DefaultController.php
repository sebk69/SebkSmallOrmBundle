<?php

namespace Sebk\SmallOrmBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('SebkSmallOrmBundle:Default:index.html.twig', array('name' => $name));
    }
}
