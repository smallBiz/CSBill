<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
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

            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new APY\DataGridBundle\APYDataGridBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\UserBundle\FOSUserBundle(),

            new CSBill\CoreBundle\CSBillCoreBundle(),
            new CSBill\InstallBundle\CSBillInstallBundle(),
            new CSBill\ClientBundle\CSBillClientBundle(),
            new CSBill\DataGridBundle\CSBillDataGridBundle(),
            new CSBill\QuoteBundle\CSBillQuoteBundle(),
            new CSBill\InvoiceBundle\CSBillInvoiceBundle(),
            new CSBill\ItemBundle\CSBillItemBundle(),
            new CSBill\SettingsBundle\CSBillSettingsBundle(),
            new CSBill\UserBundle\CSBillUserBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new JMS\DebuggingBundle\JMSDebuggingBundle($this);
        }

        return $bundles;
    }

    /**
     * Return the name of the cached container class
     *
     * @return string
     */
    public function getContainerCacheClass()
    {
        return $this->getContainerClass();
    }

    protected function getContainerBaseClass()
    {
        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            return '\JMS\DebuggingBundle\DependencyInjection\TraceableContainer';
        }

        return parent::getContainerBaseClass();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
