<?php
namespace Paypal\PaypalIntegrationBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Export the citizen profile.
 */
class PaypalTransactionStatusCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //define the command name.
        $this->setName('paypaltransactionstatus:check')
             ->setDescription('check the transaaction status on paypal');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $data = array();
       $paypal_service = $this->getContainer()->get('paypal_integration.paypal_transaction_check');
       $paypal_service->verifyPaypalTransactionStatus();
       $output->writeln('DONE');
    }
}