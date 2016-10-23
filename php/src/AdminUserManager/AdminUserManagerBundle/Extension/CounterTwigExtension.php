<?php
 
namespace AdminUserManager\AdminUserManagerBundle\Extension;
 
class CounterTwigExtension extends \Twig_Extension
{
    private $em;
    private $conn;
 
    public function __construct(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
        $this->conn = $em->getConnection();
    }
 
    public function getFunctions()
    {
        return array(
            'citizen' => new \Twig_Function_Method($this, 'getCitizen'),
            'store' => new \Twig_Function_Method($this, 'getStore')
        );
    }
 
    public function getCitizen()
    {
        
        $sql = "SELECT count(*) FROM CitizenUser";
        return $this->conn->fetchColumn($sql);
    }
    public function getStore()
    {
        $sql = "SELECT count(*) FROM Store";
        return $this->conn->fetchColumn($sql);
    }
 
    public function getName()
    {
        return 'counter_twig_extension';
    }
}