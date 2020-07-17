<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;


use phootwork\collection\Map;

/**
 * Object to hold vendor specific information.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Vendor
{
    const MYSQL_ENGINE = 'Engine';
    const MYSQL_AUTO_INCREMENT = 'AutoIncrement';
    const MYSQL_AVG_ROW_LENGTH = 'AvgRowLength';
    const MYSQL_CHARSET = 'Charset';
    const MYSQL_CHECKSUM = 'Checksum';
    const MYSQL_COLLATE = 'Collate';
    const MYSQL_CONNECTION = 'Connection';
    const MYSQL_DATA_DIRECTORY = 'DataDirectory';
    const MYSQL_DELAY_KEY_WRITE = 'DelayKeyWrite';
    const MYSQL_INDEX_DIRECTORY = 'IndexDirectory';
    const MYSQL_INSERT_METHOD = 'InsertMethod';
    const MYSQL_KEY_BLOCK_SIZE = 'KeyBlockSize';
    const MYSQL_MAX_ROWS = 'MaxRows';
    const MYSQL_MIN_ROWS = 'MinRows';
    const MYSQL_PACK_KEYS = 'PackKeys';
    const MYSQL_ROW_FORMAT = 'RowFormat';
    const MYSQL_UNION = 'Union';
    const MYSQL_INDEX_TYPE = 'Index_type';

    const ORACLE_PCT_FREE = 'PCTFree';
    const ORACLE_INIT_TRANS = 'InitTrans';
    const ORACLE_MIN_EXTENTS = 'MinExtents';
    const ORACLE_MAX_EXTENTS = 'MaxExtents';
    const ORACLE_PCT_INCREASE = 'PCTIncrease';
    const ORACLE_TABLESPACE = 'Tablespace';
    const ORACLE_PK_PCT_FREE = 'PKPCTFree';
    const ORACLE_PK_INIT_TRANS = 'PKInitTrans';
    const ORACLE_PK_MIN_EXTENTS = 'PKMinExtents';
    const ORACLE_PK_MAX_EXTENTS = 'PKMaxExtents';
    const ORACLE_PK_PCT_INCREASE = 'PKPCTIncrease';
    const ORACLE_PK_TABLESPACE = 'PKTablespace';

    private string $type;
    private Map $parameters;

    /**
     * Creates a new VendorInfo instance.
     *
     * @param string $type       RDBMS type (optional)
     * @param array  $parameters An associative array of vendor's parameters (optional)
     */
    public function __construct(?string $type = null, array $parameters = [])
    {
        $this->parameters = new Map($parameters);

        if (null !== $type) {
            $this->setType($type);
        }
    }

    /**
     * Sets the RDBMS type for this vendor specific information.
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns the RDBMS type for this vendor specific information.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets a parameter value.
     *
     * @param string $name The parameter name
     * @param mixed $value The parameter value
     */
    public function setParameter(string $name, $value): void
    {
        $this->parameters->set($name, $value);
    }

    /**
     * Returns a parameter value.
     *
     * @param string $name The parameter name
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters->get($name);
    }

    /**
     * Returns whether or not a parameter exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return $this->parameters->has($name);
    }

    /**
     * Sets an associative array of parameters for vendor specific information.
     *
     * @param array $parameters Parameter data.
     *
     */
    public function setParameters(array $parameters = [])
    {
        $this->parameters->setAll($parameters);
    }

    /**
     * Returns an associative array of parameters for
     * vendor specific information.
     *
     * @return Map
     */
    public function getParameters(): Map
    {
        return $this->parameters;
    }

    /**
     * Returns whether or not this vendor info is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->parameters->isEmpty();
    }

    /**
     * Returns a new VendorInfo object that combines two VendorInfo objects.
     *
     * @param Vendor $info
     * @return Vendor
     */
    public function getMergedVendorInfo(Vendor $info): Vendor
    {
        $params = array_merge($this->parameters->toArray(), $info->getParameters()->toArray());

        $newInfo = new Vendor($this->type);
        $newInfo->setParameters($params);

        return $newInfo;
    }
}
