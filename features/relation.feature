Feature: Relation
  In order to map the relations between my business objects to the database
  As a developer
  I need to be able to describe relations in a schema

  Background:
    Given I have the standard configuration

  Scenario: Successfully create a table with One-To-Many relation
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="a_property" type="VARCHAR" default="Property value" />
          <field name="a_number" type="FLOAT" />
        </entity>
        <entity name="b_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="b_property" type="VARCHAR" default="Property value" />
          <field name="b_number" type="FLOAT" />
          <relation target="a_table" />
        </entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `b_table`
      (
          `id` INTEGER NOT NULL,
          `b_property` VARCHAR(255) DEFAULT 'Property value',
          `b_number` FLOAT,
          `a_table_id` INTEGER,
          PRIMARY KEY (`id`),
          UNIQUE (`id`),
          FOREIGN KEY (`a_table_id`) REFERENCES `a_table` (`id`)
      );
      """

  Scenario: Successfully create a table with Many-To-Many relation
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="a_property" type="VARCHAR" default="Property value" />
          <field name="a_number" type="FLOAT" />
        </entity>
        <entity name="b_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="b_property" type="VARCHAR" default="Property value" />
          <field name="b_number" type="FLOAT" />
        </entity>
        <entity name="a_x_b" isCrossRef="true">
          <field name="a_id" primaryKey="true" type="INTEGER" />
          <field name="b_id" primaryKey="true" type="INTEGER" />
          <relation target="a_table" onDelete="cascade">
              <reference local="a_id" foreign="id"/>
          </relation>
          <relation target="b_table" onDelete="cascade">
              <reference local="b_id" foreign="id"/>
          </relation>
        </entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `a_x_b`
      (
          `a_id` INTEGER NOT NULL,
          `b_id` INTEGER NOT NULL,
          PRIMARY KEY (`a_id`,`b_id`),
          UNIQUE (`a_id`,`b_id`),
          FOREIGN KEY (`a_id`) REFERENCES `a_table` (`id`)
              ON DELETE CASCADE,
          FOREIGN KEY (`b_id`) REFERENCES `b_table` (`id`)
              ON DELETE CASCADE
      );
      """

  Scenario: Successfully create a table with One-To-One relation
    Given I have XML schema:
      """
      <database name="default">
        <entity name="a_table">
          <field name="id" type="INTEGER" required="true" primaryKey="true" />
          <field name="a_property" type="VARCHAR" default="Property value" />
          <field name="a_number" type="FLOAT" />
        </entity>
        <entity name="b_table">
          <field name="a_id" type="INTEGER" required="true" primaryKey="true" />
          <field name="b_property" type="VARCHAR" default="Property value" />
          <field name="b_number" type="FLOAT" />
          <relation target="a_table">
            <reference local="a_id" foreign="id" />
          </relation>
        </entity>
      </database>
      """
    When I generate SQL
    Then It should contain:
      """
      CREATE TABLE `b_table`
      (
          `a_id` INTEGER NOT NULL,
          `b_property` VARCHAR(255) DEFAULT 'Property value',
          `b_number` FLOAT,
          PRIMARY KEY (`a_id`),
          UNIQUE (`a_id`),
          FOREIGN KEY (`a_id`) REFERENCES `a_table` (`id`)
      );
      """