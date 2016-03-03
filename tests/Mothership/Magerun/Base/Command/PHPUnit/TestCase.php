<?php

namespace Mothership\Magerun\Base\Command\PHPUnit;

use N98\Magento\Application;


/**
 * Class TestCase
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\PHPUnit
 */
class TestCase extends \N98\Magento\Command\PHPUnit\TestCase
{
    /**
     * @var Application
     */
    private $application = null;

    /**
     *
     */
    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Application
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = $this->getTestMagentoRoot();

            $this->application = $this->getMock(
                'N98\Magento\Application',
                array('getMagentoRootFolder')
            );
            $loader = require __DIR__ . '/../../../../../../../../vendor/autoload.php';
            $this->application->setAutoloader($loader);
            $this->application->expects($this->any())->method('getMagentoRootFolder')->will($this->returnValue($root));

            spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));

            $this->application->init();
            $this->application->initMagento();
            if ($this->application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
                spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));
            }
        }

        return $this->application;
    }
}