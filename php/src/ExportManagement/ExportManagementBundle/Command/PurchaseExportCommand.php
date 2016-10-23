<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Export the Purchase.
 */
class PurchaseExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('purchaseexport:export')
             ->setDescription('Export purchase on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $purchase_import_service = $this->getContainer()->get('export_management.purchase_import_export_command_service');
       $purchase_import_service->purchaseexport(); //export the purchase.
       $output->writeln('DONE');
    }
}