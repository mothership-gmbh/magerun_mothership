PHPUnit tests class
----------------------------
Base classes for unit tests:
- [MothershipBaseTestCase](https://github.com/mothership-gmbh/component_base/blob/develop/src/Mothership/Tests/MothershipBaseTestCase.php) is useful for tests private and protected methods plus some others features.
- [MothershipMysqlAbstractTestCase](https://github.com/mothership-gmbh/component_base/blob/develop/src/Mothership/Tests/MothershipMysqlAbstractTestCase.php) is useful to make unit test that interact with the database.
- Il file xml is just an example to how configure PHPUnit to have access to the db
- The yaml file represents a database structure and data example to interact with the database. The constructor in the class *MothershipMysqlAbstractTestCase*, starting from this file creates the tables in the database and populates them with the values of the file. After the tests execution all the databases class will be drop.


