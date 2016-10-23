<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export the shop profile.
 */
class InCassiExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        //php app/console salesincassi:export
        $this->setName('salesincassi:export')
             ->setDescription('Export sales into incassi file on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       //get service for incassi sales export 
       $sale_incassi_export_service = $this->getContainer()->get('export_management.incassi_sale_export_command_service');
       $sale_incassi_export_service->salesexport();
       $output->writeln('DONE');
    }
}