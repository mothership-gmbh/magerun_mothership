<?php
/**
 * Mothership GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to office@mothership.de so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.mothership.de for more information.
 *
 * @category  Mothership
 * @package   Mothership_Aigner
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright Copyright (c) 2016 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */
namespace Mothership\Magerun\Queue\Jobs;

/**
 * Class AbstractJob
 *
 * @category  Mothership
 * @package   Mothership_Aigner
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *
 */
abstract class AbstractJob
{
    /**
     * @var mixed
     */
    public $args;

    /**
     * Define the yaml configuration file
     *
     * @var string
     */
    public $workflow_configuration_file;

    /**
     * Reinitialize the database connection due process fork issues
     *
     * @throws \Mothership\StateMachine\Exception\StateMachineException
     */
    public function init()
    {
        /**
         * Due to a massive bug in the pcnt_fork process which led to a disconnect of the parent
         * PDO process, the PDO need to be spawned again. This is currently the only solution i know about.
         *
         * @see \Magento_Db_Adapter_Pdo_Mysql for more details about the PDO implementation
         */
        \Mage::unregister('_singleton/core/resource');
        \Mage::getSingleton('core/resource')->getConnection('core_read');
        \Mage::getSingleton('core/resource')->getConnection('core_write');

        $this->args['pid']    = posix_getppid();
        $this->args['output'] = new \Symfony\Component\Console\Output\ConsoleOutput();
        if (null !== $this->args['pid']) {
            //$this->args['debug'] = false;
        }

        if (false == array_key_exists('workflow', $this->args)) {
            throw new \Exception('Missing mandatory parameter: ' . 'workflow');
        }
    }

    /**
     * Run the state machine with the workflow file defined.
     *
     * @return void
     */
    public function run()
    {
        $configuration_path = $this->args['root-dir'] . '/app/etc/mothership/workflows/' . $this->args['workflow'];

        // run the workflow
        $stateMachine = new \Mothership\StateMachine\StateMachine($configuration_path);
        $stateMachine->run($this->args);
    }
}