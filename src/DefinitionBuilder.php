<?php

namespace Pulsar;

use ICanBoogie\Inflector;
use Pulsar\Relation\Relationship;

final class DefinitionBuilder
{
    const DEFAULT_ID_PROPERTY = [
        'type' => Type::INTEGER,
        'mutable' => Property::IMMUTABLE,
    ];

    const AUTO_TIMESTAMPS = [
        'created_at' => [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
        ],
        'updated_at' => [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
        ],
    ];

    const SOFT_DELETE_TIMESTAMPS = [
        'deleted_at' => [
            'type' => Type::DATE,
            'validate' => 'timestamp|db_timestamp',
            'null' => true,
        ],
    ];

    /** @var Definition[] */
    private static $definitions;

    /**
     * Gets the definition for a model. If needed the
     * definition will be generated. It will only be
     * generated once.
     */
    public static function get(string $modelClass): Definition
    {
        /** @var Model $modelClass */
        if (!isset(self::$definitions[$modelClass])) {
            self::$definitions[$modelClass] = $modelClass::buildDefinition();
        }

        return self::$definitions[$modelClass];
    }

    /**
     * Builds a model definition given certain parameters.
     */
    public static function build(array $properties, string $modelClass, bool $autoTimestamps, bool $softDelete): Definition
    {
        /** @var Model $modelClass */
        // add in the default ID property
        if (!isset($properties[Model::DEFAULT_ID_NAME]) && $modelClass::getIDProperties() == [Model::DEFAULT_ID_NAME]) {
            $properties[Model::DEFAULT_ID_NAME] = self::DEFAULT_ID_PROPERTY;
        }

        // generates created_at and updated_at timestamps
        if ($autoTimestamps) {
            $properties = array_replace(self::AUTO_TIMESTAMPS, $properties);
        }

        // generates deleted_at timestamps
        if ($softDelete) {
            $properties = array_replace(self::SOFT_DELETE_TIMESTAMPS, $properties);
        }

        $result = [];
        foreach ($properties as $k => $property) {
            // handle relationship shortcuts
            if (isset($property['relation']) && !isset($property['relation_type'])) {
                self::buildBelongsToLegacy($k, $property);
            } elseif (isset($property['belongs_to'])) {
                self::buildBelongsTo($property, $result);
            } elseif (isset($property['has_one'])) {
                self::buildHasOne($property, $modelClass);
            } elseif (isset($property['belongs_to_many'])) {
                self::buildBelongsToMany($property, $modelClass);
            } elseif (isset($property['has_many'])) {
                self::buildHasMany($property, $modelClass);
            }

            $result[$k] = new Property($property, $k);
        }

        // order the properties array by name for consistency
        // since it is constructed in a random order
        ksort($result);

        return new Definition($result);
    }

    /////////////////////////////////
    // Relationship Shortcuts
    /////////////////////////////////

    /**
     * This is added for BC with older versions of pulsar
     * that only supported belongs to relationships.
     */
    private static function buildBelongsToLegacy(string $name, array &$property): void
    {
        $property['relation_type'] = Relationship::BELONGS_TO;
        // in the legacy configuration the default local key was the property name
        $property['local_key'] = $property['local_key'] ?? $name;

        // the default foreign key is `id`
        if (!isset($property['foreign_key'])) {
            $property['foreign_key'] = Model::DEFAULT_ID_NAME;
        }
    }

    private static function buildBelongsTo(array &$property, array &$result): void
    {
        $property['relation_type'] = Relationship::BELONGS_TO;
        $property['relation'] = $property['belongs_to'];
        $property['persisted'] = false;

        // the default foreign key is `id`
        if (!isset($property['foreign_key'])) {
            $property['foreign_key'] = Model::DEFAULT_ID_NAME;
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!isset($property['local_key'])) {
            $inflector = Inflector::get();
            $property['local_key'] = strtolower($inflector->underscore($property['relation']::modelName())).'_id';
        }

        // when a belongs_to relationship is used then we automatically add a
        // new property for the ID field which gets persisted to the DB
        if (!isset($result[$property['local_key']])) {
            $result[$property['local_key']] = new Property([
                'type' => Type::INTEGER,
            ], $property['local_key']);
        }
    }

    private static function buildBelongsToMany(array &$property, string $modelClass): void
    {
        /* @var Model $modelClass */
        $property['relation_type'] = Relationship::BELONGS_TO_MANY;
        $property['relation'] = $property['belongs_to_many'];
        $property['persisted'] = false;

        // the default local key would look like `user_id`
        // for a model named User
        if (!isset($property['local_key'])) {
            $inflector = Inflector::get();
            $property['local_key'] = strtolower($inflector->underscore($property['relation']::modelName())).'_id';
        }

        if (!isset($property['foreign_key'])) {
            $property['foreign_key'] = Model::DEFAULT_ID_NAME;
        }

        // the default pivot table name looks like
        // RoleUser for models named Role and User.
        // the tablename is built from the model names
        // in alphabetic order.
        if (!isset($property['pivot_tablename'])) {
            $names = [
                $modelClass::modelName(),
                $property['relation']::modelName(),
            ];
            sort($names);
            $property['pivot_tablename'] = implode($names);
        }
    }

    private static function buildHasOne(array &$property, string $modelClass): void
    {
        /* @var Model $modelClass */
        $property['relation_type'] = Relationship::HAS_ONE;
        $property['relation'] = $property['has_one'];
        $property['persisted'] = false;

        // the default foreign key would look like
        // `user_id` for a model named User
        if (!isset($property['foreign_key'])) {
            $inflector = Inflector::get();
            $property['foreign_key'] = strtolower($inflector->underscore($modelClass::modelName())).'_id';
        }

        if (!isset($property['local_key'])) {
            $property['local_key'] = Model::DEFAULT_ID_NAME;
        }
    }

    private static function buildHasMany(array &$property, string $modelClass): void
    {
        /* @var Model $modelClass */
        $property['relation_type'] = Relationship::HAS_MANY;
        $property['relation'] = $property['has_many'];
        $property['persisted'] = false;

        // the default foreign key would look like
        // `user_id` for a model named User
        if (!isset($property['foreign_key'])) {
            $inflector = Inflector::get();
            $property['foreign_key'] = strtolower($inflector->underscore($modelClass::modelName())).'_id';
        }

        // the default local key is `id`
        if (!isset($property['local_key'])) {
            $property['local_key'] = Model::DEFAULT_ID_NAME;
        }
    }
}
