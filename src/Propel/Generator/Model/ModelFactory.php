<?php
namespace Propel\Generator\Model;

class ModelFactory
{

    public static function createMappingModel($name, $attributes): MappingModelInterface {
        $className = NamingTool::toUpperCamelCase($name);
        $className = '\\Propel\\Generator\\Model\\' . $className;
        
        if (class_exists($className)) {
            $instance = new $className();
            $instance->loadMapping($attributes);
            return $instance;
        }
        
        return null; // or throw exception?
    }
}