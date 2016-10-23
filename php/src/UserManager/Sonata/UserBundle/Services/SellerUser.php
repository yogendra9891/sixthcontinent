<?php
namespace UserManager\Sonata\UserBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use UserManager\Sonata\UserBundle\Entity\EmailVerificationToken;

// service method class for seller user handling.
class SellerUser {

    protected $em;
    protected $dm;
    protected $container;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    /**
     * Check a seller is exists for a shop
     * @param int $shop_id
     * @param int $user_id
     * @return boolean
     */
    public function checkSellerExists($shop_id, $user_id) {
        $em = $this->em;
        $seller_user = $em->getRepository('UserManagerSonataUserBundle:SellerUser')
                          ->findOneBy(array('shopId'=>$shop_id, 'sellerId'=>$user_id));
        if (!$seller_user) { //if seller does not exists
          return false;  
        }
        return true;
    }
    
    /**
     * create seller in table [SellerUser]
     * @param int $shop_id
     * @param int $user_id
     * @param int $owner_id
     * @return int $seller_record_id
     */
    public function createSeller($user_name, $email, $password, $firstname, $lastname, $phone) {
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $verification_accesstoken = $tokenGenerator->generateToken();
        $userManager = $this->container->get('fos_user.user_manager');
        //get user manager instance
        $user = $userManager->createUser();
        //set username
        $user->setUsername($user_name);
        //set email
        $user->setEmail($email);
        //set and encrypt password
        $user->setPlainPassword($password);
        $user->setEnabled(1);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setPhone($phone);
        $user->setCitizenProfile(0);
        $user->setBrokerProfile(0);
        $user->setStoreProfile(0);
        $user->setSellerProfile(1);
        $user->setVerificationStatus('UNVERIFIED');
        $user->setCountry('IT');
        $user->setProfileImg('');
        $user->setProfileType(5);
        $user->setVerificationToken($verification_accesstoken);
        $time = new \DateTime('now');
        $user->setVerifylinkCreatedAt($time);
        //get email constraint object
        $emailConstraint = new EmailConstraint(); 

        $errors = $this->container->get('validator')->validateValue(
                $email, $emailConstraint
        );

        // check email validation
        if (count($errors) > 0) {
            $res_data = array('code' => 135, 'message' => 'EMAIL_IS_INVALID', 'data' => array());
            return $res_data;
        }

        //handling exception
        try {
            $check_success = $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            $res_data = array('code' => 136, 'message' => 'USER_EXIST', 'data' => array());
            return $res_data;
        }
        
        $register_id = $user->getId();
        //map seller user to shop
        $res_data = array('code' => 101, 'data' => array('register_id' => $register_id, 'verification_token' => $verification_accesstoken));
        return $res_data;
    }
    
    /**
     * Add email verification token
     * @param int $register_id
     * @param string $tokenGenerator
     * @return boolean
     */
    public function addEmailVerificationToken($register_id, $tokenGenerator)
    {
        $expiry = time() + (3600*24*2); //for 2 days
        $em = $this->container->get('doctrine')->getManager();
        $time = new \DateTime('now');
        $emailVerificationToken = new EmailVerificationToken();
        $emailVerificationToken->setUserId($register_id);
        $emailVerificationToken->setVerificationToken($tokenGenerator);
        $emailVerificationToken->setCreatedAt($time);
        $emailVerificationToken->setUpdatedAt($time);
        $emailVerificationToken->setIsActive(1);
        $emailVerificationToken->setExpiryAt($expiry);
        try{
        $em->persist($emailVerificationToken);
        $em->flush();
        }catch(\Exception $e){
            
        }
        return true;
    }
    
    
}