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
 * Class General
 *
 * @category  Mothership
 * @package   Mothership_Aigner
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2016 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *            Use this job for a general purpose
 *
 *            VVERBOSE=1 QUEUE=intex-import APP_INCLUDE=bootstrap-phpunit.php php ../vendor/chrisboulton/php-resque/bin/resque
 */
class General extends AbstractJob
{
    /**
     * Run the product import job
     *
     * @throws \Mothership\StateMachine\Exception\StateMachineException
     */
    public function perform()
    {
        parent::init();
        $this->run();
    }
}