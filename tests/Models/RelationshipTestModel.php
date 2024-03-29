<?php

namespace Pulsar\Tests\Models;

use Pulsar\Model;
use Pulsar\Property;

class RelationshipTestModel extends Model
{
    protected static function getProperties(): array
    {
        return [
            'person' => new Property(
                persisted: false,
                in_array: true,
            ),
        ];
    }

    protected function getPersonValue()
    {
        return new Person(['id' => 10, 'name' => 'Bob Loblaw', 'email' => 'bob@example.com']);
    }
}
