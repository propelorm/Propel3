<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Model;
use Propel\Generator\Platform\PlatformInterface;
use phootwork\collection\Set;

/**
 * Trait SqlPart
 *
 * @author Thomas Gossmann
 */
trait SqlPart
{
    use SuperordinatePart;

    /** @var bool */
    protected $heavyIndexing;

    /** @var bool */
    protected $identifierQuoting;

    /** @var string */
    protected $stringFormat;

    /** @var string */
    protected $idMethod;

    /** @var Set */
    protected $idMethodParameters;

    protected function initSql()
    {
        $this->idMethodParameters = new Set();
    }

    /**
     * @return null|PlatformInterface
     */
    abstract public function getPlatform(): ?PlatformInterface;

    /**
     * Sets the method strategy for generating primary keys.
     *
     * @param string $idMethod
     * @return $this
     */
    public function setIdMethod(string $idMethod)
    {
        $this->idMethod = $idMethod;

        return $this;
    }

    /**
     * Returns the method strategy for generating primary keys.
     *
     * [HL] changing behavior so that Database default method is returned
     * if no method has been specified for the entity.
     *
     * @return string
     */
    public function getIdMethod(): string
    {
        if (null !== $this->idMethod) {
            return $this->idMethod;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getIdMethod')) {
            return $this->getSuperordinate()->getIdMethod();
        }

        return Model::DEFAULT_ID_METHOD;
    }

    /**
     * Adds a new parameter for the strategy that generates primary keys.
     *
     * @param IdMethodParameter $idMethodParameter
     * @return $this
     */
    public function addIdMethodParameter(IdMethodParameter $idMethodParameter)
    {
        $idMethodParameter->setEntity($this);
        $this->idMethodParameters->add($idMethodParameter);

        return $this;
    }

    /**
     * Returns a Collection of parameters relevant for the chosen
     * id generation method.
     *
     * @return IdMethodParameter[]
     */
    public function getIdMethodParameters(): array
    {
        return $this->idMethodParameters->toArray();
    }

    /**
     * Removes a parameter for the strategy that generates primary keys.
     *
     * @param IdMethodParameter $idMethodParameter
     * @return $this
     */
    public function removeIdMethodParameter(IdMethodParameter $idMethodParameter)
    {
        $idMethodParameter->setEntity(null);
        $this->idMethodParameters->remove($idMethodParameter);

        return $this;
    }

    /**
     * Sets heavy indexing
     *
     * @param bool $heavyIndexing
     * @return $this
     */
    public function setHeavyIndexing(bool $heavyIndexing)
    {
        $this->heavyIndexing = $heavyIndexing;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHeavyIndexing(): bool
    {
        if (null !== $this->heavyIndexing) {
            return $this->heavyIndexing;
        }
    }

    /**
     * @param bool $identifierQuoting
     * @return $this
     */
    public function setIdentifierQuoting(bool $identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;

        return $this;
    }

    /**
     * Checks if identifierQuoting is enabled. Looks up to its database->isIdentifierQuotingEnabled
     * if identifierQuoting is null hence undefined.
     *
     * Use getIdentifierQuoting() if you need the raw value.
     *
     * @return bool
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        if (null !== $this->identifierQuoting) {
            return $this->identifierQuoting;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'isIdentifierQuotingEnabled')) {
            return $this->getSuperordinate()->isIdentifierQuotingEnabled();
        }
    }

    /**
     * @return bool|null
     */
    public function getIdentifierQuoting()
    {
        return $this->identifierQuoting;
    }

    /**
     * Quotes a identifier depending on identifierQuotingEnabled.
     *
     * Needs a platform assigned to its database.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        if (!$this->getPlatform()) {
            throw new \RuntimeException(
                'No platform specified. Can not quote without knowing which platform this entity\'s database is using.'
            );
        }

        if ($this->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }


    /**
     * Sets the default string format for ActiveRecord objects in this entity.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param  string $format
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setStringFormat(string $format)
    {
        $formats = Model::SUPPORTED_STRING_FORMATS;
        $format = strtoupper($format);

        if (!in_array($format, $formats)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Given "%s" default string format is not supported. Only "%s" are valid string formats.',
                    $format,
                    implode(', ', $formats)
                )
            );
        }

        $this->stringFormat = $format;

        return $this;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this entity,
     * or the one for the whole database if not set.
     *
     * @return string
     */
    public function getStringFormat(): string
    {
        if (null !== $this->stringFormat) {
            return $this->stringFormat;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getStringFormat')) {
            return $this->getSuperordinate()->getStringFormat();
        }

        return Model::DEFAULT_STRING_FORMAT;
    }
}
