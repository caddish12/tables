<?php

namespace Services\Template\Builders;

use Route;
use Tests\TestCase;
use LaravelEnso\Helpers\app\Classes\Obj;
use LaravelEnso\Tables\app\Services\Template\Builders\Structure;

class StructureTest extends TestCase
{
    private $meta;
    private $template;

    protected function setUp() :void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();

        $this->createRoute();

        $this->template = new Obj([
            'routePrefix' => 'prefix',
            'dataRouteSuffix' => 'suffix',
        ]);

        $this->meta = new Obj([]);
    }


    /** @test */
    public function can_build_with_route()
    {
        $this->build();

        $this->assertEquals('/test', $this->template->get('readPath'));
    }

    /** @test */
    public function can_build_with_length_menu()
    {
        $options = [12, 24];

        $this->template->set('lengthMenu', $options);

        $this->build();

        $this->assertEquals($options[0], $this->meta->get('length'));
    }

    private function createRoute($name = 'prefix.suffix', $path = '/test'): \Illuminate\Routing\Route
    {
        $route = Route::any($path)->name($name);

        Route::getRoutes()->refreshNameLookups();

        return $route;
    }

    private function build(): void
    {
        (new Structure(
            $this->template,
            $this->meta
        ))->build();
    }
}
