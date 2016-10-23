<?php
namespace ExportManagement\ExportManagementBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Export the citizen profile.
 */
class CitizenExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('citizenprofile:export')
             ->setDescription('Export citizen profile on daily basis');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
       $serviceUrl = $baseUrl . 'webapi/exportcitizenprofile';
       $client = new CurlRequestService();
       $response = $client->send('POST', $serviceUrl, array(), $data)
                          ->getResponse();
       $output->writeln('DONE');
    }
}