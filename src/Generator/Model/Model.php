<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Model;

class Model
{
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_PROTECTED = 'protected';

    const ID_METHOD_NATIVE = 'native';
    const ID_METHOD_NONE = 'none';

    const DEFAULT_TYPE = 'VARCHAR';
    const DEFAULT_ID_METHOD = Model::ID_METHOD_NATIVE;
    const DEFAULT_STRING_FORMAT = 'YAML';
    const DEFAULT_ACCESSOR_ACCESSIBILITY = Model::VISIBILITY_PUBLIC;
    const DEFAULT_MUTATOR_ACCESSIBILITY = Model::VISIBILITY_PUBLIC;

    const SUPPORTED_STRING_FORMATS = ['XML', 'YAML', 'JSON', 'CSV'];

    const RELATION_NONE = '';           // No 'ON [ DELETE | UPDATE]' behavior
    const RELATION_NOACTION = 'NO ACTION';
    const RELATION_CASCADE = 'CASCADE';
    const RELATION_RESTRICT = 'RESTRICT';
    const RELATION_SETDEFAULT = 'SET DEFAULT';
    const RELATION_SETNULL = 'SET NULL';
}
