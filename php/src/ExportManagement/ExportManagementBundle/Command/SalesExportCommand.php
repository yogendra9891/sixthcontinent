<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Export the sales.
 */
class SalesExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('salesexport:export')
             ->setDescription('Export sales on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $sales_export_service = $this->getContainer()->get('export_management.sales_import_export_command_service');
       $sales_export_service->salesexport(); //export the sales
       $output->writeln('DONE');
    }
}