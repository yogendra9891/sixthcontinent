<?php
namespace SixthContinent\SixthContinentConnectBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * command for tamoil coupon export
 */
class TamoilCouponExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        //php app/console tamoilcoupon:export
        $this->setName('tamoilcoupon:export')
             ->setDescription('export the connect ci transaction.');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $export_connect_service = $this->getContainer()->get('sixth_continent_connect.tamoil_coupon_export');
       $export_connect_service->exportTamoilCoupon();
       $output->writeln('DONE');
    }
}