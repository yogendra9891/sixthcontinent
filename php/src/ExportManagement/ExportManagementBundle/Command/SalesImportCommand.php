<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Import the sales.
 */
class SalesImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('salesimport:import')
             ->setDescription('Import sales on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $sales_import_service = $this->getContainer()->get('export_management.sales_import_export_command_service');
       //$sales_import_service->salesimport(); //sales import old structure(from applane)
       //$sales_import_service->salesimporttransactions(); //from our database
       $sales_import_service->salesDataimport(); //for manual and system types
       $output->writeln('DONE');
    }
}