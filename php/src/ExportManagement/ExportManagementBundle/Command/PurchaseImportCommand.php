<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Import the Purchase.
 */
class PurchaseImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        //php app/console purchaseimport:import
        $this->setName('purchaseimport:import')
             ->setDescription('Import purchase on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    { 
       $data = array();
       $purchase_import_service = $this->getContainer()->get('export_management.purchase_import_export_command_service');
       $purchase_import_service->purchaseimport(); //import the purchase
       $output->writeln('DONE');
    }
}