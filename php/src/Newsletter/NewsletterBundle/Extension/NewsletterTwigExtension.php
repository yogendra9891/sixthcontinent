<?php
 
namespace Newsletter\NewsletterBundle\Extension;
 
class NewsletterTwigExtension extends \Twig_Extension
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
            'categories' => new \Twig_Function_Method($this, 'getCategories'),
        );
    }
 
    public function getCategories()
    {
        
        $sql = "SELECT * FROM Template ORDER BY id";
        return $this->conn->fetchAll($sql);
    }
 
    public function getName()
    {
        return 'news_letter_twig_extension';
    }
}