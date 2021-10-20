<?php

namespace AwStudio\DynamicRelations;

use Illuminate\Support\ServiceProvider;

class DynamicRelationsServiceProvider extends ServiceProvider
{
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../migrations/2021_00_00_000000_create_dynamic_relations_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_dynamic_relations_table.php'),
        ], 'dynamic-relations:migrations');
    }
}
