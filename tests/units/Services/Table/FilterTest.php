<?php

namespace Services\Table;

use Faker\Factory;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Tables\app\Services\Table\Filters;

class FilterTest extends TestCase
{
    private $testModel;
    private $faker;
    private $query;
    private $params;

    public function setUp(): void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->faker = Factory::create();

        $this->createTestModelTable();

        $this->createRelationModelTable();

        $this->testModel = $this->createTestModel();
        $this->query = FilterTestModel::select('*');
        $this->params = ['meta' => []];

        $this->createRelationModel();
    }

    /** @test */
    public function can_get_data_without_condition()
    {
        $this->params['columns']['name'] = ['data' => 'name'];

        $response = $this->requestResponse();

        $this->assertCount(FilterTestModel::count(), $response);

        $this->assertTrue(
            $response->pluck('name')
                ->contains($this->testModel->name)
        );
    }

    /** @test */
    public function can_use_search()
    {
        $this->params['columns']['name'] = [
            'data' => 'name',
            'meta' => ['searchable' => true]
        ];

        $this->params['meta'] = [
            'search' => $this->testModel->name,
            'comparisonOperator' => 'LIKE',
        ];

        $response = $this->requestResponse();

        $this->assertCount(1, $response);

        $this->assertEquals(
            $response->first()->name,
            $this->testModel->name
        );

        $this->params['meta']['search'] = $this->testModel->name.'-';

        $response = $this->requestResponse();

        $this->assertCount(0, $response);
    }

    /** @test */
    public function can_use_multi_argument_search()
    {
        $this->params['columns'] = [
            'name' => [
                'data' => 'name',
                'searchable' => true,
                'meta' => ['searchable' => true]
            ],
            'appellative' => [
                'data' => 'appellative',
                'searchable' => true,
                'meta' => ['searchable' => true]
            ],
        ];

        $this->params['meta'] = [
            'search' => $this->testModel->name.' '.$this->testModel->appellative,
            'comparisonOperator' => 'LIKE',
        ];

        $response = $this->requestResponse();

        $this->assertCount(1, $response);

        $this->assertEquals(
            $response->first()->name,
            $this->testModel->name
        );
    }

    /** @test */
    public function can_use_relation_search()
    {
        $this->params['columns']['name'] = [
            'name' => 'relation.name',
            'data' => 'relation.name',
            'meta' => ['searchable' => true],
        ];

        $this->params['meta'] = [
            'search' => $this->testModel->relation->name,
            'comparisonOperator' => 'LIKE'
        ];

        $response = $this->requestResponse();

        $this->assertCount(1, $response);

        $this->assertEquals(
            $response->first()->name,
            $this->testModel->name
        );
    }

    /** @test */
    public function can_use_interval()
    {
        $this->params['intervals']['filter_test_models']['id'] = [
            'min' => $this->testModel->id - 1,
            'max' => $this->testModel->id + 1,
        ];

        $response = $this->requestResponse();

        $this->assertCount(1, $response);

        $this->assertEquals(
            $this->testModel->name,
            $response->first()->name
        );

        $this->params['intervals']['filter_test_models']['id'] = [
            'min' => $this->testModel->id - 1,
            'max' => $this->testModel->id - 2,
        ];

        $response = $this->requestResponse();

        $this->assertCount(0, $response);
    }

    /** @test */
    public function can_use_date_interval()
    {
        $this->params['intervals']['filter_test_models']['created_at'] = [
            'dbDateFormat' => 'Y-m-d',
            'dateFormat' => 'Y-m-d H:i:s',
            'min' => $this->testModel->created_at->subDays(1),
            'max' => $this->testModel->created_at->addDays(1),
        ];

        $response = $this->requestResponse();

        $this->assertCount(1, $response);

        $this->assertEquals(
            $this->testModel->name,
            $response->first()->name
        );

        $this->params['intervals']['filter_test_models']['created_at'] = [
            'dbDateFormat' => 'Y-m-d',
            'dateFormat' => 'Y-m-d H:i:s',
            'min' => $this->testModel->created_at->subDays(2),
            'max' => $this->testModel->created_at->subDays(1),
        ];

        $response = $this->requestResponse();

        $this->assertCount(0, $response);
    }

    /** @test */
    public function can_use_filters()
    {
        $this->params['filters']['filter_test_models'] = ['name' => $this->testModel->name];

        $response = $this->requestResponse();

        $this->assertCount(1, $response);

        $this->assertEquals(
            $response->first()->name,
            $this->testModel->name
        );

        $this->params['filters']['filter_test_models'] = ['name' => $this->testModel->name.'-'];

        $response = $this->requestResponse();

        $this->assertCount(0, $response);
    }

    private function requestResponse()
    {
        (new Filters(
            new Obj($this->params),
            $this->query
        ))->handle();

        return $this->query->get();
    }

    private function createTestModel()
    {
        return FilterTestModel::create([
            'appellative' => $this->faker->firstName,
            'name' => $this->faker->name,
        ]);
    }

    private function createRelationModel()
    {
        return FilterRelationModel::create([
            'name' => $this->faker->word,
            'parent_id' => $this->testModel->id,
        ]);
    }

    private function createTestModelTable()
    {
        Schema::create('filter_test_models', function ($table) {
            $table->increments('id');
            $table->string('appellative')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    private function createRelationModelTable()
    {
        Schema::create('filter_relation_models', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('filter_test_models');
            $table->timestamps();
        });
    }
}

class FilterTestModel extends Model
{
    protected $fillable = ['name', 'appellative', 'created_at'];

    public function relation()
    {
        return $this->hasOne(FilterRelationModel::class, 'parent_id');
    }
}

class FilterRelationModel extends Model
{
    protected $fillable = ['name', 'parent_id'];
}
