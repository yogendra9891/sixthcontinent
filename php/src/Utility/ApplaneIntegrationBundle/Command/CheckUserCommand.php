<?php
namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;
use Symfony\Component\Console\Input\InputOption;


class CheckUserCommand extends ContainerAwareCommand
{
   /**
    * Configure coomad
    */
   protected function configure()
   {
       $this
           ->setName('checkuser:run')
           ->setDescription('Check user on applane from local db')
           ->addOption(
               'filter',
                null,
                 InputOption::VALUE_REQUIRED,
                'Check for registration date of users',
                null
            )
           ;
   }

   /**
    * Execute command
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    */
   protected function execute(InputInterface $input, OutputInterface $output)
   {
        //notification service
        $applane_user_service = $this->getContainer()->get('appalne_integration.applaneusers');
        $applane_shop_service = $this->getContainer()->get('appalne_integration.applaneshops');
        $applane_userprofile_service = $this->getContainer()->get('appalne_integration.applaneusersprofile');
        $applane_userfriend_service = $this->getContainer()->get('appalne_integration.applaneusersfriend');
        $applane_userfollower_service = $this->getContainer()->get('appalne_integration.applaneusersfollower');
        $applane_userimage_service = $this->getContainer()->get('appalne_integration.applaneusersimage');
        $applane_shopprofile_service = $this->getContainer()->get('appalne_integration.applaneshopprofile');
        $applane_shopimage_service = $this->getContainer()->get('appalne_integration.applaneshopimage');
        $applane_shopfollowers_service = $this->getContainer()->get('appalne_integration.applaneshopfollowers');
        $applane_shopfavs_service = $this->getContainer()->get('appalne_integration.applaneshopfavs');
        $applane_shopreferrals_service = $this->getContainer()->get('appalne_integration.applaneshopreferrals');
        $applane_shopuserfollowers_service = $this->getContainer()->get('appalne_integration.applaneusershopfollowers');
        $applane_shopuserfavs_service = $this->getContainer()->get('appalne_integration.applaneshopuserfavs');
        $applane_userreferral_service = $this->getContainer()->get('appalne_integration.applaneuserreferral');
        $filter = $input->getOption('filter');
        $filter = strtoupper($filter);
        switch($filter){
            case 'USER':
                $response = $applane_user_service->checkUsersOnApplane($filter);
                break;
            case 'SHOP':
                $response = $applane_shop_service->checkShopsOnApplane($filter);
                break;
            case 'USER_PROFILE':
                $response = $applane_userprofile_service->checkUsersProfileOnApplane($filter);
                break;
            case 'USER_FRIEND':
                $response = $applane_userfriend_service->checkUsersFriendOnApplane($filter);
                break;
            case 'USER_FOLLOWER':
                $response = $applane_userfollower_service->checkUsersFollowerOnApplane($filter);
                break;
            case 'USER_IMAGE':
                $response = $applane_userimage_service->checkUsersImageOnApplane($filter);
                break;
            case 'SHOP_PROFILE':
                $response = $applane_shopprofile_service->checkShopsProfileOnApplane($filter);
                break;
            case 'SHOP_PROFILE_IMAGE':
                $response = $applane_shopimage_service->checkShopsImageOnApplane($filter);
                break;
            case 'SHOP_FOLLOWERS':
                $response = $applane_shopfollowers_service->checkShopFollowersOnApplane($filter);
                break;
            case 'SHOP_FAV_USERS':
                $response = $applane_shopfavs_service->checkShopFavsOnApplane($filter);
                break;
            case 'SHOP_REFERRALS':
                $response = $applane_shopreferrals_service->checkShopReferralsOnApplane($filter);
                break;
            case 'SHOP_USER_FOLLOWERS':
                $response = $applane_shopuserfollowers_service->checkShopFollowersOnApplane($filter);
                break;
            case 'SHOP_USER_FAV_USERS':
                $response = $applane_shopuserfavs_service->checkShopFavsOnApplane($filter);
                break;
            case 'USER_REFERRALS':
                $response = $applane_userreferral_service->checkUserReferralsOnApplane($filter);
                break;
            default:
                $output->writeln('No filter found.');
                break;
        }
        $output->writeln('DONE');
   }
}