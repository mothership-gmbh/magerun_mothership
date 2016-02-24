<?php
namespace Mothership\Tests;

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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mothership
 * @package   Mothership_Component
 * @author    Maurizio Brioschi <brioschi@mothership.de>
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright Copyright (c) 2015 Mothership GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.mothership.de/
 */

use Mothership\Tests\TraitBase;

/**
 * Class MothershipMysqlAbstractTestCase
 *
 * @category  Mothership
 * @package   Mothership_MothershipMysqlAbstractTestCase
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 *
 * @see       https://phpunit.de/manual/current/en/database.html
 */
abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    use TraitBase;

    /**
     * @var \PDO
     */
    private $pdo = null;

    /**
     * @var string
     */
    protected $dataset;

    /**
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     *
     * @link http://someguyjeremy.com/2013/01/database-testing-with-phpunit.html
     */
    public function getConnection()
    {
        if (null === $this->pdo) {
            $this->pdo = new \PDO('sqlite::memory:');

            /**
             *
             */
            $fixtureDataSet = $this->getDataSet($this->dataset);
            foreach ($fixtureDataSet->getTableNames() as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS ".$table.";");


                $meta = $fixtureDataSet->getTableMetaData($table);
                $create = "CREATE TABLE IF NOT EXISTS `".$table."`";
                $cols = array();
                foreach ($meta->getColumns() as $col) {
                    $cols[] = " `".$col."` VARCHAR(200)";
                }
                $create .= '('.implode(',', $cols).');';
                $this->pdo->exec($create);
            }
        }
        return $this->createDefaultDBConnection($this->pdo, ':memory:');
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($this->dataset);
    }


}
