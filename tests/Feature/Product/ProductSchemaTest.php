<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_catalog_filter_column_is_indexed(): void
    {
        $indexed = collect(Schema::getIndexes('products'))->pluck('columns')->map(fn (array $columns) => $columns[0]);

        foreach (['karat', 'type', 'gold_color_id', 'stone_type_id', 'weight_grams'] as $column) {
            $this->assertContains($column, $indexed, "products.$column should lead an index");
        }
    }
}
