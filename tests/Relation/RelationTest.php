<?php

namespace Pulsar\Tests\Relation;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Model;
use Pulsar\Query;
use Pulsar\Tests\Models\TestModel;

class RelationTest extends MockeryTestCase
{
    public function testGetLocalModel()
    {
        $model = Mockery::mock(Model::class);
        $relation = new TestAbstractRelation($model, 'user_id', TestModel::class, 'id');

        $this->assertEquals($model, $relation->getLocalModel());
    }

    public function testGetLocalKey()
    {
        $model = Mockery::mock(Model::class);
        $relation = new TestAbstractRelation($model, 'user_id', TestModel::class, 'id');

        $this->assertEquals('user_id', $relation->getLocalKey());
    }

    public function testGetForeignModel()
    {
        $model = Mockery::mock(Model::class);
        $relation = new TestAbstractRelation($model, 'user_id', TestModel::class, 'id');

        $this->assertEquals(TestModel::class, $relation->getForeignModel());
    }

    public function testGetForeignKey()
    {
        $model = Mockery::mock(Model::class);
        $relation = new TestAbstractRelation($model, 'user_id', TestModel::class, 'id');

        $this->assertEquals('id', $relation->getForeignKey());
    }

    public function testGetQuery()
    {
        $model = Mockery::mock(Model::class);
        $relation = new TestAbstractRelation($model, 'user_id', TestModel::class, 'id');

        $query = $relation->getQuery();
        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(['test' => true], $query->getWhere());
    }

    public function testCallOnQuery()
    {
        $model = Mockery::mock(Model::class);
        $relation = new TestAbstractRelation($model, 'user_id', TestModel::class, 'id');

        $query = $relation->where(['name' => 'Bob']);

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(['test' => true, 'name' => 'Bob'], $query->getWhere());
    }
}
