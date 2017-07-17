<?php
namespace Propel\Generator\Model;

class Model
{
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_PROTECTED = 'protected';

    const ID_METHOD_NATIVE = 'native';
    const ID_METHOD_NONE = 'none';

    const DEFAULT_ID_METHOD = Model::ID_METHOD_NATIVE;
    const DEFAULT_STRING_FORMAT = 'YAML';
    const DEFAULT_ACCESSOR_ACCESSIBILITY = Model::VISBILITY_PUBLIC;
    const DEFAULT_MUTATOR_ACCESSIBILITY = Model::VISBILITY_PUBLIC;
}

