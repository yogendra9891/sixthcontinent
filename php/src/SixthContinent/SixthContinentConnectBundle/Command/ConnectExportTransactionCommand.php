<?php
namespace SixthContinent\SixthContinentConnectBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * command for connect transaction ci back.
 */
class ConnectExportTransactionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        //php app/console connecttransaction:export
        $this->setName('connecttransaction:export')
             ->setDescription('export the connect ci transaction.'); //php app/console connecttransaction:export
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $export_connect_service = $this->getContainer()->get('sixth_continent_connect.connect_export_transaction_app');
       $export_connect_service->exportCiTransaction();
       $output->writeln('DONE');
    }
}