<?php
namespace SixthContinent\SixthContinentConnectBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * command for connect transaction back.
 */
class ConnectTransactionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        //php app/console connecttransaction:checkstatus
        $this->setName('connecttransaction:checkstatus')
             ->setDescription('check the connect transaction status on paypal');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $paypal_connect_service = $this->getContainer()->get('sixth_continent_connect.paypal_cron_connect_app');
       $paypal_connect_service->ConnectPaypalTransactionStatus();
       $output->writeln('DONE');
    }
}