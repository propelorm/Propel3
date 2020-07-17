<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\collection\Set;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Model;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Trait SqlPart
 *
 * @author Thomas Gossmann
 */
trait SqlPart
{
    use SuperordinatePart;

    protected bool $heavyIndexing;
    protected bool $identifierQuoting;
    protected string $stringFormat;
    protected string $idMethod = '';
    protected Set $idMethodParameters;

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
     */
    public function setIdMethod(string $idMethod): void
    {
        $this->idMethod = $idMethod;
    }

    /**
     * Returns the method strategy for generating primary keys.
     *
     * [HL] changing behavior so that Database default method is returned
     * if no method has been specified for the entity.
     */
    public function getIdMethod(): string
    {
        if ('' !== $this->idMethod) {
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
     */
    public function addIdMethodParameter(IdMethodParameter $idMethodParameter): void
    {
        $idMethodParameter->setEntity($this);
        $this->idMethodParameters->add($idMethodParameter);
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
     */
    public function removeIdMethodParameter(IdMethodParameter $idMethodParameter): void
    {
        $idMethodParameter->setEntity(null);
        $this->idMethodParameters->remove($idMethodParameter);
    }

    /**
     * Sets heavy indexing
     *
     * @param bool $heavyIndexing
     */
    public function setHeavyIndexing(bool $heavyIndexing = null): void
    {
        $this->heavyIndexing = $heavyIndexing ?? true;
    }

    /**
     * @return bool
     */
    public function isHeavyIndexing(): bool
    {
        if (isset($this->heavyIndexing)) {
            return $this->heavyIndexing;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'isHeavyIndexing')) {
            return $this->getSuperordinate()->isHeavyIndexing();
        }

        return false;
    }

    /**
     * @param bool $identifierQuoting
     */
    public function setIdentifierQuoting(bool $identifierQuoting): void
    {
        $this->identifierQuoting = $identifierQuoting;
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
        if (isset($this->identifierQuoting)) {
            return $this->identifierQuoting;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'isIdentifierQuotingEnabled')) {
            return $this->getSuperordinate()->isIdentifierQuotingEnabled();
        }
    }

    /**
     * @return bool|null
     */
    public function getIdentifierQuoting(): ?bool
    {
        return $this->identifierQuoting ?? null;
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
     */
    public function setStringFormat(string $format): void
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
    }

    /**
     * Returns the default string format for ActiveRecord objects in this entity,
     * or the one for the whole database if not set.
     *
     * @return string
     */
    public function getStringFormat(): string
    {
        if (isset($this->stringFormat)) {
            return $this->stringFormat;
        }

        if ($this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getStringFormat')) {
            return $this->getSuperordinate()->getStringFormat();
        }

        return Model::DEFAULT_STRING_FORMAT;
    }
}
