Feature: Table
  In order to map my business objects to the database
  As a developer
  I need to be able to describe my entities in a schema

  Background:
    Given I have the standard configuration

  Scenario: Successfully create a table
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table"></entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `a_table`
      """

  Scenario: Successfully create a table with some properties
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table">
          <field name="id" type="INTEGER" required="true" />
          <field name="property" type="VARCHAR" default="Property value" />
          <field name="number" type="FLOAT" />
        </entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `a_table`
      (
          `id` INTEGER NOT NULL,
          `property` VARCHAR(255) DEFAULT 'Property value',
          `number` FLOAT
      );
      """

  Scenario: Successfully create a table with primary key
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="property" type="VARCHAR" default="Property value" />
          <field name="number" type="FLOAT" />
        </entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `a_table`
      (
          `id` INTEGER NOT NULL,
          `property` VARCHAR(255) DEFAULT 'Property value',
          `number` FLOAT,
          PRIMARY KEY (`id`),
          UNIQUE (`id`)
      );
      """

  Scenario: Successfully create a table with index
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="property" type="VARCHAR" default="Property value" />
          <field name="number" type="FLOAT" />
          <index>
            <index-field name="property" />
          </index>
          <unique>
            <unique-field name="number" />
          </unique>
        </entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `a_table`
      (
          `id` INTEGER NOT NULL,
          `property` VARCHAR(255) DEFAULT 'Property value',
          `number` FLOAT,
          PRIMARY KEY (`id`),
          UNIQUE (`number`),
          UNIQUE (`id`)
      );

      CREATE INDEX `a_table_i_62547c` ON `a_table` (`property`);
      """