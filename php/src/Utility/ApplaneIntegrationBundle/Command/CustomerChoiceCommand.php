<?php
namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;
use Symfony\Component\Console\Input\InputOption;


class CustomerChoiceCommand extends ContainerAwareCommand
{
   /**
    * Configure coomad
    */
   protected function configure()
   {
       $this
           ->setName('customerchoice:run')
           ->setDescription('Update customer on applane from local db')
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
        $applane_service = $this->getContainer()->get('appalne_integration.applanecustomerchoice');
        $filter = $input->getOption('filter');
        $filter = strtoupper($filter);
        $response = $applane_service->updateCustomerChoice($filter);
       
        $output->writeln('DONE');
   }
}