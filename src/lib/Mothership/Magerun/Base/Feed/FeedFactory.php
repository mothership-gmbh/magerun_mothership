<?php
/**
 * This file is part of the Mothership GmbH code.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */
namespace Mothership\Magerun\Base\Feed;

use Mothership\Component\Feed\FactoryFeedAbstract;

/**
 * Class FeedFactory
 *
 * @category  Mothership_Magerun
 * @package   Mothership_Magerun_Addons
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 *            Extend the FactoryFeedAbstract as we use dynamic database configuration
 */
class FeedFactory extends FactoryFeedAbstract
{
    /**
     * @param string $mapping_path
     * @param mixed  $extended_configuration
     *
     * @throws \Mothership\Component\Feed\Exception\FactoryFeedException
     */
    public function __construct($mapping_path, $inputInterface)
    {
        parent::__construct($mapping_path, $inputInterface);
    }
}