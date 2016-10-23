<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

date_default_timezone_set("Europe/Rome");

class AppKernel extends Kernel {

    public function registerBundles() {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Sonata\jQueryBundle\SonatajQueryBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new \Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            new UserManager\Sonata\UserBundle\UserManagerSonataUserBundle(),
            new Notification\NotificationBundle\NManagerNotificationBundle(),
            new FOS\MessageBundle\FOSMessageBundle(),
            new Message\MessageBundle\MessageMessageBundle(),
            new Sonata\IntlBundle\SonataIntlBundle(),
            new Sonata\MediaBundle\SonataMediaBundle(),
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Application\Sonata\MediaBundle\ApplicationSonataMediaBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new IamPersistent\MongoDBAclBundle\IamPersistentMongoDBAclBundle(),
            new Acme\GiftBundle\AcmeGiftBundle(),
            new NoiseLabs\Bundle\NuSOAPBundle\NoiseLabsNuSOAPBundle(),
            new Post\PostBundle\PostPostBundle(),
            new Problematic\AclManagerBundle\ProblematicAclManagerBundle(),
            new Newsletter\NewsletterBundle\NewsletterNewsletterBundle(),
            new Stfalcon\Bundle\TinymceBundle\StfalconTinymceBundle(),
            new StoreManager\StoreBundle\StoreManagerStoreBundle(),
            new StoreManager\PostBundle\StoreManagerPostBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Dashboard\DashboardManagerBundle\DashboardManagerBundle(),
            new Media\MediaBundle\MediaMediaBundle(),
            new AdminUserManager\AdminUserManagerBundle\AdminUserManagerAdminUserManagerBundle(),
            new Affiliation\AffiliationManagerBundle\AffiliationAffiliationManagerBundle(),
            new CardManagement\CardManagementBundle\CardManagementBundle(),
            new Ijanki\Bundle\FtpBundle\IjankiFtpBundle(),
            new Transaction\TransactionBundle\TransactionTransactionBundle(),
            new ExportManagement\ExportManagementBundle\ExportManagementBundle(),
            new WalletManagement\WalletBundle\WalletManagementWalletBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
            new Payment\PaymentProcessBundle\PaymentPaymentProcessBundle(),
            new Payment\PaymentDistributionBundle\PaymentPaymentDistributionBundle(),
            new Utility\RatingBundle\UtilityRatingBundle(),
            new Utility\PhotoCommentBundle\PhotoCommentBundle(),
            new Utility\RequestHandlerBundle\UtilityRequestHandlerBundle(), 
            new Utility\CurlBundle\UtilityCurlBundle(),
            new Utility\SecurityBundle\UtilitySecurityBundle(),
            new Payment\TransactionProcessBundle\PaymentTransactionProcessBundle(),
            new Utility\FacebookBundle\UtilityFacebookBundle(),
            new Applane\WrapperApplaneBundle\ApplaneWrapperApplaneBundle(),
            new Utility\UniversalNotificationsBundle\UtilityUniversalNotificationsBundle(),
            new Utility\ApplaneIntegrationBundle\UtilityApplaneIntegrationBundle(),
            new Paypal\PaypalIntegrationBundle\PaypalIntegrationBundle(),
            new Utility\UtilityBundle\UtilityUtilityBundle(),
            new PostFeeds\PostFeedsBundle\PostFeedsBundle(),
            new Votes\VotesBundle\VotesVotesBundle(),
            new SixthContinent\SixthContinentConnectBundle\SixthContinentConnectBundle(),
            new Utility\MasterDataBundle\MasterDataBundle(),
            new Utility\BarcodeBundle\UtilityBarcodeBundle(),
            new Transaction\TransactionSystemBundle\TransactionSystemBundle(),
            new Transaction\WalletBundle\WalletBundle(),
            new Transaction\CommercialPromotionBundle\CommercialPromotionBundle(),
            new Transaction\CitizenIncomeBundle\CitizenIncomeBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Acme\DemoBundle\AcmeDemoBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader) {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }

}
