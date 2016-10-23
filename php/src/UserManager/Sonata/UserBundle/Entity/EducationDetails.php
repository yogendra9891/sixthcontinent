<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EducationDetails
 */
class EducationDetails
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $school;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var string
     */
    private $degree;

    /**
     * @var string
     */
    private $fieldOfStudy;

    /**
     * @var string
     */
    private $grade;

    /**
     * @var string
     */
    private $activities;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EducationDetails
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set school
     *
     * @param string $school
     * @return EducationDetails
     */
    public function setSchool($school)
    {
        $this->school = $school;
    
        return $this;
    }

    /**
     * Get school
     *
     * @return string 
     */
    public function getSchool()
    {
        return $this->school;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return EducationDetails
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    
        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return EducationDetails
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    
        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set degree
     *
     * @param string $degree
     * @return EducationDetails
     */
    public function setDegree($degree)
    {
        $this->degree = $degree;
    
        return $this;
    }

    /**
     * Get degree
     *
     * @return string 
     */
    public function getDegree()
    {
        return $this->degree;
    }

    /**
     * Set fieldOfStudy
     *
     * @param string $fieldOfStudy
     * @return EducationDetails
     */
    public function setFieldOfStudy($fieldOfStudy)
    {
        $this->fieldOfStudy = $fieldOfStudy;
    
        return $this;
    }

    /**
     * Get fieldOfStudy
     *
     * @return string 
     */
    public function getFieldOfStudy()
    {
        return $this->fieldOfStudy;
    }

    /**
     * Set grade
     *
     * @param string $grade
     * @return EducationDetails
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
    
        return $this;
    }

    /**
     * Get grade
     *
     * @return string 
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Set activities
     *
     * @param string $activities
     * @return EducationDetails
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;
    
        return $this;
    }

    /**
     * Get activities
     *
     * @return string 
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EducationDetails
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EducationDetails
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return EducationDetails
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    /**
     * @var smallint
     */
    private $visibility;


    /**
     * Set visibility
     *
     * @param \smallint $visibility
     * @return EducationDetails
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    
        return $this;
    }

    /**
     * Get visibility
     *
     * @return \smallint  
     */
    public function getVisibility()
    {
        return $this->visibility;
    }
    /**
     * @var boolean
     */
    private $currentlyAttending;


    /**
     * Set currentlyAttending
     *
     * @param boolean $currentlyAttending
     * @return EducationDetails
     */
    public function setCurrentlyAttending($currentlyAttending)
    {
        $this->currentlyAttending = $currentlyAttending;
    
        return $this;
    }

    /**
     * Get currentlyAttending
     *
     * @return boolean 
     */
    public function getCurrentlyAttending()
    {
        return $this->currentlyAttending;
    }
}