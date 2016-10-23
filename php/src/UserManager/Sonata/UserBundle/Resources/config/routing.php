<?php
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('business_category_list', new Route('businesscategorylist', array(
    '_controller' => 'UserManagerSonataUserBundle:UserBusiness:businessCategoryList',
)));

return $collection;