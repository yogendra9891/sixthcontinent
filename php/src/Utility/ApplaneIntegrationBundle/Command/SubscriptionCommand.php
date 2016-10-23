<?php
namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;


class SubscriptionCommand extends ContainerAwareCommand
{
   /**
    * Configure coomad
    */
   protected function configure()
   {
       $this
           ->setName('subscription:run')
           ->setDescription('Perform subscription')
           ;
   }

   /**
    * Execute command
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    */
   protected function execute(InputInterface $input, OutputInterface $output)
   {
        //call service
        $subscription_service = $this->getContainer()->get('card_management.subscription');
        $response = $subscription_service->recurringSubscription();
        $output->writeln('SUCCESS');
   }
}