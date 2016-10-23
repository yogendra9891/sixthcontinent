<?php
namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;


class RecurringCommand extends ContainerAwareCommand
{
   /**
    * Configure coomad
    */
   protected function configure()
   {
       $this
           ->setName('recurring:run')
           ->setDescription('Perform recurring')
           ;
   }

   /**
    * Execute command
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    */
   protected function execute(InputInterface $input, OutputInterface $output)
   {
        $data = array();
       //call service
        $recurring_service = $this->getContainer()->get('recurring_shop.payment');
        $response = $recurring_service->payrecurringpayment();
        $output->writeln('SUCCESS');
   }
}