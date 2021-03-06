<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
//use AppBundle\Entity\EducationDetails;

/**
 * EducationDetailsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EducationDetailsRepository extends EntityRepository
{

    public function InsertEducationDetails($data){
        
        $details = new EducationDetails();

        if($data['id']){
            $details = $this->find($data['id']);    
            if (!$details) {
                $data = array('code' => 131, 'message' => 'INVALID_DATA', 'data' => array());
                echo json_encode($data);
                exit;
            }
        } else {
            $details->setCreatedAt( new \DateTime("now"));
        }
        
        if($data['currently_attending'])
        {
            $qb = $this->createQueryBuilder('edu');
            $query = $qb
                    ->update()
                    ->set('edu.currentlyAttending', '0')
                    ->set('edu.endDate', $data['start_date'])
                    ->where('edu.userId =:user_id AND edu.currentlyAttending = :attendind')
                    ->setParameter('user_id',$data['user_id'] )
                    ->setParameter('attendind',1 )
                    ->getQuery();

            $query->getResult();    
        }
        
        $details->setUserId($data['user_id']);
        $details->setSchool($data['school']);
        $details->setStartDate($data['start_date']);
        $details->setEndDate($data['end_date']);
        $details->setCurrentlyAttending($data['currently_attending']);
        $details->setDegree($data['degree']);
        $details->setFieldOfStudy($data['field_of_study']);
        $details->setGrade($data['grade']);
        $details->setActivities($data['activities']);
        $details->setDescription($data['desc']);
        $details->setVisibility($data['visibility_type']);
        $details->setUpdatedAt( new \DateTime("now"));
        
        $em = $this->getEntityManager();

        $em->persist($details);
        $em->flush();
        
        $educationDetail = array();
        
        $educationDetail['id'] = $details->getId();
        $educationDetail['user_id'] = $details->getUserId();
        $educationDetail['school'] = $details->getSchool();
        $educationDetail['start_date'] = $details->getStartDate();
        $educationDetail['end_date'] = $details->getEndDate();
        $educationDetail['currently_attending'] = $details->getCurrentlyAttending();
        $educationDetail['degree'] = $details->getDegree();
        $educationDetail['field_of_study'] = $details->getFieldOfStudy();
        $educationDetail['grade'] = $details->getGrade();
        $educationDetail['activities'] = $details->getActivities();
        $educationDetail['description'] = $details->getDescription();
        $educationDetail['visibility_type'] = $details->getVisibility();
        
        return $educationDetail;

    }

    public function getEducationDetails($user_id)
    {
        $educationDetails = $this->findBy( array('userId' => $user_id));
        
        $response = array();
        $currentEdu= array();
        foreach($educationDetails as $details)
        {
            if($details->getCurrentlyAttending()){
                $currentEdu['id'] = $details->getId();
                $currentEdu['user_id'] = $details->getUserId();
                $currentEdu['school'] = $details->getSchool();
                $currentEdu['start_date'] = $details->getStartDate();
                $currentEdu['end_date'] = date('Y');
                $currentEdu['currently_attending'] = $details->getCurrentlyAttending();
                $currentEdu['degree'] = $details->getDegree();
                $currentEdu['field_of_study'] = $details->getFieldOfStudy();
                $currentEdu['grade'] = $details->getGrade();
                $currentEdu['activities'] = $details->getActivities();
                $currentEdu['description'] = $details->getDescription();
                $currentEdu['visibility_type'] = $details->getVisibility();
            }else{
                $array = array();

                $array['id'] = $details->getId();
                $array['user_id'] = $details->getUserId();
                $array['school'] = $details->getSchool();
                $array['start_date'] = $details->getStartDate();
                $array['end_date'] = $details->getEndDate();
                $array['currently_attending'] = $details->getCurrentlyAttending();
                $array['degree'] = $details->getDegree();
                $array['field_of_study'] = $details->getFieldOfStudy();
                $array['grade'] = $details->getGrade();
                $array['activities'] = $details->getActivities();
                $array['description'] = $details->getDescription();
                $array['visibility_type'] = $details->getVisibility();

                $response[] = $array; 
            }
            
        }
        
        if(!empty($currentEdu)){
            array_unshift($response, $currentEdu);
        }
        
        return $response;

    }
    
    /*
    * Deleting Education Detail
    */
    public function DeleteEducationDetails($id, $user_id)
    {
        
        $EducationDetail = $this->findOneBy(array('id'=>$id, 'userId'=>$user_id ));
        if (!$EducationDetail)
        {
            $data = array('code' => 131, 'message' => 'INVALID_DATA', 'data' => array());
            echo json_encode($data);
            exit;
        }
        
        $em = $this->getEntityManager();
        $em->remove($EducationDetail);
        $em->flush();
        
        return true;

    }

    
    /*
    * Setting Visibility of educational details 
    */
    public function SetEducationDetailVisibility($data)
    {
        
        $details = $this->findOneBy(array('id'=>$data['id'], 'userId'=>$data['user_id'] ));    
        if (!$details) {
            $data = array('code' => 131, 'message' => 'INVALID_DATA', 'data' => array());
            echo json_encode($data);
            exit;
        }
            
        $details->setVisibility($data['visibility_type']);

        $em = $this->getEntityManager();

        $em->persist($details);
        $em->flush();
        
        return true;
    }
    
    public function getFriendEducationDetails($visibility_type, $user_id)
    {
        if($visibility_type == '3')
        {
            $educationDetails = $this->findBy( array('userId' => $user_id, 'visibility' => array(1,2,3) ));
        } elseif($visibility_type == '2') {
            $educationDetails = $this->findBy( array('userId' => $user_id, 'visibility' => array(2,3) ));
        } elseif($visibility_type == '1') {
            $educationDetails = $this->findBy( array('userId' => $user_id, 'visibility' => array(1,3) ));
        } else {
            $educationDetails = $this->findBy( array('userId' => $user_id, 'visibility' => $visibility_type ));
        }
        
         
        $response = array();
        foreach($educationDetails as $details)
        {
            $array = array();
            
            $array['id'] = $details->getId();
            $array['user_id'] = $details->getUserId();
            $array['school'] = $details->getSchool();
            $array['start_date'] = $details->getStartDate();
            $array['end_date'] = $details->getEndDate();
            $array['currently_attending'] = $details->getCurrentlyAttending();
            $array['degree'] = $details->getDegree();
            $array['field_of_study'] = $details->getFieldOfStudy();
            $array['grade'] = $details->getGrade();
            $array['activities'] = $details->getActivities();
            $array['description'] = $details->getDescription();
            $array['visibility_type'] = $details->getVisibility();
            
            $response[] = $array; 
            
        }
        
        return $response;

    }
    
}

