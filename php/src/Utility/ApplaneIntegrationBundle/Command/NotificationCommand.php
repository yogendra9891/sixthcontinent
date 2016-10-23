<?php
namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;


class NotificationCommand extends ContainerAwareCommand
{
   /**
    * Configure coomad
    */
   protected function configure()
   {
       $this
           ->setName('notification:run')
           ->setDescription('Sends recurring notifications')
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
        $recurring_notification_service = $this->getContainer()->get('recurring_shop.payment_notification');
        $response = $recurring_notification_service->sendtransactionnotification();
        $output->writeln('DONE');
   }
}