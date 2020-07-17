<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Schema\Dumper;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Vendor;

/**
 * A class for dumping a schema to an XML representation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class XmlDumper implements DumperInterface
{
    /**
     * The DOMDocument object.
     *
     * @var \DOMDocument
     */
    private \DOMDocument $document;

    /**
     * Constructor.
     *
     * @param \DOMDocument $document
     */
    public function __construct(\DOMDocument $document = null)
    {
        if (null === $document) {
            $document = new \DOMDocument('1.0', 'utf-8');
            $document->formatOutput = true;
        }

        $this->document = $document;
    }

    /**
     * Dumps a single Database model into an XML formatted version.
     *
     * @param  Database $database The database model
     * @return string   The dumped XML formatted output
     */
    public function dump(Database $database): string
    {
        $this->appendDatabaseNode($database, $this->document);

        return trim($this->document->saveXML());
    }

    /**
     * Dumps a single Schema model into an XML formatted version.
     *
     * @param  Schema  $schema                The schema object
     * @return string
     */
    public function dumpSchema(Schema $schema): string
    {
        $rootNode = $this->document->createElement('app-data');
        $this->document->appendChild($rootNode);
        foreach ($schema->getDatabases() as $database) {
            $this->appendDatabaseNode($database, $rootNode);
        }

        return trim($this->document->saveXML());
    }

    /**
     * Appends the generated <database> XML node to its parent node.
     *
     * @param Database $database   The Database model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendDatabaseNode(Database $database, \DOMNode $parentNode): void
    {
        $databaseNode = $parentNode->appendChild($this->document->createElement('database'));
        $databaseNode->setAttribute('name', $database->getName()->toString());
        $databaseNode->setAttribute('defaultIdMethod', $database->getIdMethod());

        if ($schema = $database->getSchemaName()) {
            $databaseNode->setAttribute('schema', $schema->toString());
        }

        if ($namespace = $database->getNamespace()) {
            $databaseNode->setAttribute('namespace', $namespace->toString());
        }

        $defaultAccessorVisibility = $database->getAccessorVisibility();
        if ($defaultAccessorVisibility !== Model::VISIBILITY_PUBLIC) {
            $databaseNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $database->getMutatorVisibility();
        if ($defaultMutatorVisibility !== Model::VISIBILITY_PUBLIC) {
            $databaseNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        $defaultStringFormat = $database->getStringFormat();
        if (Model::DEFAULT_STRING_FORMAT !== $defaultStringFormat) {
            $databaseNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        if ($database->isHeavyIndexing()) {
            $databaseNode->setAttribute('heavyIndexing', 'true');
        }

        /*
            FIXME - Before we can add support for domains in the schema, we need
            to have a method of the Field that indicates whether the field was mapped
            to a SPECIFIC domain (since Field->getDomain() will always return a Domain object)

            foreach ($this->domainMap as $domain) {
                $this->appendDomainNode($databaseNode);
            }
         */
        foreach ($database->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $databaseNode);
        }

        foreach ($database->getEntities() as $entity) {
            $this->appendEntityNode($entity, $databaseNode);
        }
    }

    /**
     * Appends the generated <vendor> XML node to its parent node.
     *
     * @param Vendor $vendorInfo The VendorInfo model instance
     * @param \DOMNode   $parentNode The parent DOMNode object
     */
    private function appendVendorInformationNode(Vendor $vendorInfo, \DOMNode $parentNode): void
    {
        //It's an empty Vendor created by VendorPart::getVendorByType method
        if ($vendorInfo->getParameters()->isEmpty()) {
            return;
        }

        $vendorNode = $parentNode->appendChild($this->document->createElement('vendor'));
        $vendorNode->setAttribute('type', $vendorInfo->getType());

        foreach ($vendorInfo->getParameters() as $key => $value) {
            $parameterNode = $this->document->createElement('parameter');
            $parameterNode->setAttribute('name', $key);
            $parameterNode->setAttribute('value', $value);
            $vendorNode->appendChild($parameterNode);
        }
    }

    /**
     * Appends the generated <entity> XML node to its parent node.
     *
     * @param Entity    $entity      The Entity model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendEntityNode(Entity $entity, \DOMNode $parentNode): void
    {
        $entityNode = $parentNode->appendChild($this->document->createElement('entity'));
        $entityNode->setAttribute('name', $entity->getName()->toString());

        $database = $entity->getDatabase();
        $schema = $entity->getSchemaName();
        if ($schema && $schema !== $database->getSchemaName()) {
            $entityNode->setAttribute('schema', $schema);
        }

        if (Model::ID_METHOD_NATIVE !== ($idMethod = $entity->getIdMethod())) {
            $entityNode->setAttribute('idMethod', $idMethod);
        }

        if ($tableName = $entity->getTableName()) {
            $entityNode->setAttribute('tableName', $tableName->toString());
        }

        if ($namespace = $entity->getNamespace()) {
            $entityNode->setAttribute('namespace', $namespace->toString());
        }

        if ($entity->isSkipSql()) {
            $entityNode->setAttribute('skipSql', 'true');
        }

        if ($entity->isCrossRef()) {
            $entityNode->setAttribute('isCrossRef', 'true');
        }

        if ($entity->isReadOnly()) {
            $entityNode->setAttribute('readOnly', 'true');
        }

        if ($entity->isReloadOnInsert()) {
            $entityNode->setAttribute('reloadOnInsert', 'true');
        }

        if ($entity->isReloadOnUpdate()) {
            $entityNode->setAttribute('reloadOnUpdate', 'true');
        }

        if ($referenceOnly = $entity->isForReferenceOnly()) {
            $entityNode->setAttribute('forReferenceOnly', $referenceOnly ? 'true' : 'false');
        }

        if (!$entity->getAlias()->isEmpty()) {
            $entityNode->setAttribute('alias', $entity->getAlias()->toString());
        }

        if ($entity->hasDescription()) {
            $entityNode->setAttribute('description', $entity->getDescription()->toString());
        }

        $defaultStringFormat = $entity->getStringFormat();
        if (Model::DEFAULT_STRING_FORMAT !== $defaultStringFormat) {
            $entityNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        $defaultAccessorVisibility = $entity->getAccessorVisibility();
        if ($defaultAccessorVisibility !== Model::VISIBILITY_PUBLIC) {
            $entityNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $entity->getMutatorVisibility();
        if ($defaultMutatorVisibility !== Model::VISIBILITY_PUBLIC) {
            $entityNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        foreach ($entity->getFields() as $field) {
            $this->appendFieldNode($field, $entityNode);
        }

        foreach ($entity->getRelations() as $relation) {
            $this->appendRelationNode($relation, $entityNode);
        }

        foreach ($entity->getIdMethodParameters() as $parameter) {
            $this->appendIdMethodParameterNode($parameter, $entityNode);
        }

        foreach ($entity->getIndices() as $index) {
            $this->appendIndexNode($index, $entityNode);
        }

        foreach ($entity->getUnices() as $index) {
            $this->appendUniqueIndexNode($index, $entityNode);
        }

        foreach ($entity->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $entityNode);
        }

        foreach ($entity->getBehaviors() as $behavior) {
            $this->appendBehaviorNode($behavior, $entityNode);
        }
    }

    /**
     * Appends the generated <behavior> XML node to its parent node.
     *
     * @param Behavior $behavior   The Behavior model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendBehaviorNode(Behavior $behavior, \DOMNode $parentNode): void
    {
        $behaviorNode = $parentNode->appendChild($this->document->createElement('behavior'));
        $behaviorNode->setAttribute('name', $behavior->getName()->toString());

        if ($behavior->allowMultiple()) {
            $behaviorNode->setAttribute('id', $behavior->getId());
        }

        foreach ($behavior->getParameters() as $name => $value) {
            $parameterNode = $behaviorNode->appendChild($this->document->createElement('parameter'));
            $parameterNode->setAttribute('name', $name);
            $parameterNode->setAttribute('value', is_bool($value) ? (true === $value ? 'true' : 'false') : $value);
        }
    }

    /**
     * Appends the generated <field> XML node to its parent node.
     *
     * @param Field   $field     The Field model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendFieldNode(Field $field, \DOMNode $parentNode): void
    {
        $fieldNode = $parentNode->appendChild($this->document->createElement('field'));
        $fieldNode->setAttribute('name', $field->getName()->toString());

        $fieldNode->setAttribute('type', $field->getType());

        $domain = $field->getDomain();
        if ($size = $domain->getSize()) {
            $fieldNode->setAttribute('size', (string) $size);
        }

        if ($scale = $domain->getScale()) {
            $fieldNode->setAttribute('scale', (string) $scale);
        }

        $platform = $field->getPlatform();
        if (!$field->isDefaultSqlType($platform)) {
            $fieldNode->setAttribute('sqlType', $platform->getDomainForType($field->getType())->getSqlType());
        }

        if ($field->hasDescription()) {
            $fieldNode->setAttribute('description', $field->getDescription()->toString());
        }

        if ($field->isPrimaryKey()) {
            $fieldNode->setAttribute('primaryKey', 'true');
        }

        if ($field->isAutoIncrement()) {
            $fieldNode->setAttribute('autoIncrement', 'true');
        }

        if ($field->isNotNull()) {
            $fieldNode->setAttribute('required', 'true');
        }

        $defaultValue = $domain->getDefaultValue();
        if ($defaultValue) {
            $type = $defaultValue->isExpression() ? 'defaultExpr' : 'defaultValue';
            $fieldNode->setAttribute($type, $defaultValue->getValue());
        }

        if ($field->isInheritance()) {
            $fieldNode->setAttribute('inheritance', $field->getInheritanceType());
            foreach ($field->getChildren() as $inheritance) {
                $this->appendInheritanceNode($inheritance, $fieldNode);
            }
        }

        foreach ($field->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $fieldNode);
        }
    }

    /**
     * Appends the generated <inheritance> XML node to its parent node.
     *
     * @param Inheritance $inheritance The Inheritance model instance
     * @param \DOMNode    $parentNode  The parent DOMNode object
     */
    private function appendInheritanceNode(Inheritance $inheritance, \DOMNode $parentNode): void
    {
        $inheritanceNode = $parentNode->appendChild($this->document->createElement('inheritance'));
        $inheritanceNode->setAttribute('key', $inheritance->getKey());
        $inheritanceNode->setAttribute('class', $inheritance->getClassName());

        if ($ancestor = $inheritance->getAncestor()) {
            $inheritanceNode->setAttribute('extends', $ancestor);
        }
    }

    /**
     * Appends the generated <foreign-key> XML node to its parent node.
     *
     * @param Relation $relation The Relation model instance
     * @param \DOMNode   $parentNode The parent DOMNode object
     */
    private function appendRelationNode(Relation $relation, \DOMNode $parentNode): void
    {
        $relationNode = $parentNode->appendChild($this->document->createElement('relation'));
        $relationNode->setAttribute('target', $relation->getForeignEntityName());

        if ($relation->hasName()) {
            $relationNode->setAttribute('name', $relation->getName()->toString());
        }
        $relationNode->setAttribute('field', $relation->getField());

        if ($refField = $relation->getRefField()) {
            $relationNode->setAttribute('refField', $refField);
        }

        if ($defaultJoin = $relation->getDefaultJoin()) {
            $relationNode->setAttribute('defaultJoin', $defaultJoin);
        }

        if ($onDeleteBehavior = $relation->getOnDelete()) {
            $relationNode->setAttribute('onDelete', $onDeleteBehavior);
        }

        if ($onUpdateBehavior = $relation->getOnUpdate()) {
            $relationNode->setAttribute('onUpdate', $onUpdateBehavior);
        }

        for ($i = 0, $size = $relation->getLocalFields()->size(); $i < $size; $i++) {
            $refNode = $relationNode->appendChild($this->document->createElement('reference'));
            $refNode->setAttribute('local', $relation->getLocalField($i)->getName()->toString());
            $refNode->setAttribute('foreign', $relation->getForeignFields()->get($i));
        }

        foreach ($relation->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $relationNode);
        }
    }

    /**
     * Appends the generated <id-method-parameter> XML node to its parent node.
     *
     * @param IdMethodParameter $parameter  The IdMethodParameter model instance
     * @param \DOMNode          $parentNode The parent DOMNode object
     */
    private function appendIdMethodParameterNode(IdMethodParameter $parameter, \DOMNode $parentNode): void
    {
        $idMethodParameterNode = $parentNode->appendChild($this->document->createElement('id-method-parameter'));
        $idMethodParameterNode->setAttribute('value', $parameter->getValue());
    }

    /**
     * Appends the generated <index> XML node to its parent node.
     *
     * @param Index    $index      The Index model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendIndexNode(Index $index, \DOMNode $parentNode): void
    {
        $this->appendGenericIndexNode('index', $index, $parentNode);
    }

    /**
     * Appends the generated <unique> XML node to its parent node.
     *
     * @param Unique   $index     The Unique model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendUniqueIndexNode(Unique $index, \DOMNode $parentNode): void
    {
        $this->appendGenericIndexNode('unique', $index, $parentNode);
    }

    /**
     * Appends a generice <index> or <unique> XML node to its parent node.
     *
     * @param string   $nodeType   The node type (index or unique)
     * @param Index    $index      The Index model instance
     * @param \DOMNode $parentNode The parent DOMNode object
     */
    private function appendGenericIndexNode($nodeType, Index $index, \DOMNode $parentNode): void
    {
        $indexNode = $parentNode->appendChild($this->document->createElement($nodeType));
        $indexNode->setAttribute('name', $index->getName()->toString());

        foreach ($index->getFields() as $field) {
            $indexFieldNode = $indexNode->appendChild($this->document->createElement($nodeType.'-field'));
            $indexFieldNode->setAttribute('name', $field->getName()->toString());

            if ($size = $index->getFieldSizes()->get($field->getName()->toString())) {
                $indexFieldNode->setAttribute('size', (string) $size);
            }
        }

        foreach ($index->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $indexNode);
        }
    }
}
