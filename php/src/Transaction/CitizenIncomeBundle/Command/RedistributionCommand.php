<?php

namespace Transaction\CitizenIncomeBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Export the citizen profile.
 */
class RedistributionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('redistributionprocess:start')
             ->setDescription('Export citizen profile on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();

       $export_connect_service = $this->getContainer()->get('redistribution_ci');
       $export_connect_service->reredistributionCi();
//       $export_connect_service->checkTransactionStatus();
       /*$client = new CurlRequestService();
       $response = $client->send('POST', $serviceUrl, array(), $data)
                          ->getResponse();
        * 
        */
       $output->writeln('DONE');
    }
}