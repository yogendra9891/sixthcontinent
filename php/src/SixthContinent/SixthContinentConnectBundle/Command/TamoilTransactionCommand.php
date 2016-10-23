<?php
namespace SixthContinent\SixthContinentConnectBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * command for tamoil transaction ci back.
 */
class TamoilTransactionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        //php app/console tamoiltransaction:check
        $this->setName('tamoiltransaction:check')
             ->setDescription('export the connect ci transaction.'); //php app/console connecttransaction:export
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $export_connect_service = $this->getContainer()->get('sixth_continent_connect.tamoil_offer_transaction');
       $export_connect_service->checkTransactionStatus();
       $output->writeln('DONE');
    }
}