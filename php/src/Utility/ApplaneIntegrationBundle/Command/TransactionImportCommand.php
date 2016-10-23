<?php
namespace Utility\ApplaneIntegrationBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Import the transaction.
 */
class TransactionImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('transactionimport:import')
             ->setDescription('Import transaction on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
       $serviceUrl = $baseUrl . 'webapi/importshoptransaction';
       $client = new CurlRequestService();
       $response = $client->send('POST', $serviceUrl, array(), $data)
                          ->getResponse();
       $output->writeln('DONE');
    }
}