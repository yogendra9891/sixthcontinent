<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserMultiProfileRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserMultiProfileRepository extends EntityRepository
{
    /**
	 * Get group list
	 * @param unknown_type $user_id
	 * @return array
	 */
	public function getUserRole($user_id,$user_country_code)
	{
            $users = array();
            $profile_type = array(23,25);
            //get all groups assigned to user

            //$response = array();
            $qb = $this->createQueryBuilder('c');
            $query = $qb
            ->where('c.userId =:suid','c.isActive =:isactive','c.country =:country')
            ->andWhere(
                $qb->expr()->In('c.profileType', ':profiletype')
                )
            ->setParameter('profiletype', $profile_type)
            ->setParameter('suid', $user_id)
            ->setParameter('country', $user_country_code)
            ->setParameter('isactive',1)
            ->getQuery();

            $response = $query->getResult();
            return $response;
                
	}
    /**
	 * Get user details
	 * @param unknown_type $user_id
	 * @return array
	 */
	public function getUserDetials($user_id)
	{
            $users = array();
            $profile_type = array(22,23);
            //get all groups assigned to user

            //$response = array();
            $qb = $this->createQueryBuilder('c');
            $query = $qb
            ->where('c.userId =:suid')
            ->andWhere(
                $qb->expr()->In('c.profileType', ':profiletype')
                )
            ->setParameter('profiletype', $profile_type)
            ->setParameter('suid', $user_id)
            ->getQuery();

            $response = $query->getResult();
            return $response;
                
	}

    
        /**
	 * Get user details
	 * @param integer $user_id
	 * @return array
	 */
	public function getMultiUserInfo($user_id)
	{
            $users = array();
            $response = array();
            $profile_type = array(22,23,25);
            $qb = $this->createQueryBuilder('c');
            $query = $qb
            ->select('c.userId, c.firstName, c.lastName, c.gender, c.birthDate, c.phone, c.country, c.street, c.profileType')

            ->where('c.userId =:suid')
            ->andWhere(
                $qb->expr()->In('c.profileType', ':profiletype')
                )

            ->setParameter('profiletype', $profile_type_array)

            ->setParameter('profiletype', $profile_type)

            ->setParameter('suid', $user_id)
            ->getQuery();

            $response = $query->getResult();

            if($response){
            return $response[0];
            }
            return $response;
                
	}

       /*
        * Get profile details
        * @param unknown_type $profile_type
        * @return array
        */
       public function getUserProfileDetails($user_id, $profile_type)
       {
           $users = array();
           if($profile_type == 24){
               $profile_type_array = array(24);
           }else{
               $profile_type_array = array(22,23,25);
           }
          
           //get all groups assigned to user

           //$response = array();
           $qb = $this->createQueryBuilder('c');
           $query = $qb
           ->where('c.userId =:suid')
           ->andWhere(
               $qb->expr()->In('c.profileType', ':profiletype')
               )
           ->setParameter('profiletype', $profile_type_array)
           ->setParameter('suid', $user_id)
           ->getQuery();

           $response = $query->getResult();
           return $response;
               
       }

}
