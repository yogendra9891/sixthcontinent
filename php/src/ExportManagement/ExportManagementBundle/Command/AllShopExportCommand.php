<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export the shop profile.
 */
class AllShopExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('allshopprofile:export')
             ->setDescription('Export shop profile on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $shop_export_service = $this->getContainer()->get('export_management.all_shop_export_command_service'); //get service for shop export 
       $shop_export_service->exportshopprofile();
       $output->writeln('DONE');
    }
}