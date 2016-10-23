<?php
namespace Utility\RatingBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;


// service method class for user object.
class CalculateRateService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
  

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
    
    
    /**
     * calculate the new added rate and new user count
     * @param int $total_user_count
     * @param int $total_rate
     * @param float $rate
     * @return array 
     */
    public function updateAddRate($total_user_count, $total_rate, $rate) {
        $new_total_user_count = $total_user_count + 1;
        $new_total_rate = $total_rate + $rate;
        $avg_rate = $new_total_rate / $new_total_user_count;
        return array('new_user_count' => $new_total_user_count, 'new_total_rate' => $new_total_rate, 'avg_rate' => $avg_rate);
    }

     /**
     * calculate the new added rate and new user count at remove time of rating.
     * @param int $total_user_count
     * @param int $total_rate
     * @param float $rate
     * @return array 
     */
    public function updateDeleteRate($total_user_count, $total_rate, $rate) {
        $new_total_user_count = $total_user_count - 1;
        $new_total_rate = $total_rate - $rate;
        if ($new_total_user_count > 0) {
            $avg_rate = $new_total_rate / $new_total_user_count;
        } else {
            $avg_rate = 0;
        }

        return array('new_user_count' => $new_total_user_count, 'new_total_rate' => $new_total_rate, 'avg_rate' => $avg_rate);
    }
    
     /**
     * Update rate
     * @param int $post_id
     * @param int $current_user_vote
     * @return array
     */
    public function updateEditRateCount($vote_count, $vote_sum, $post_id, $current_user_vote, $rate) {
        
        //voter count will be the same
        $total_user_count = $vote_count; //get vote count

        $total_rate = $vote_sum; //old total rate
        //remove the old rate done by user
        $total_rate_exclude_cuser = $total_rate - $current_user_vote; //new total rate
        //add the new rate done by user
        $new_total_rate = $total_rate_exclude_cuser + $rate;

        $avg_rate = $new_total_rate / $total_user_count;

        return array('total_rate' => $new_total_rate, 'avg_rate' => $avg_rate);
    }

    
}