<?php

namespace Tests\Feature;

use App\Models\Shop\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_returns_correct_keys(): void
    {
        $category = Category::factory()->create([
            'name' => 'Hőszigetelők',
            'slug' => 'hoszigetelok',
            'is_visible' => true,
        ]);
        $category->load(['products']);

        $array = $category->toSearchableArray();

        $this->assertArrayHasKey('objectID', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertEquals($category->id, $array['objectID']);
        $this->assertEquals('Hőszigetelők', $array['name']);
        $this->assertEquals('hoszigetelok', $array['slug']);
    }

    public function test_invisible_category_is_not_searchable(): void
    {
        $category = Category::factory()->create(['is_visible' => false]);

        $this->assertFalse($category->shouldBeSearchable());
    }

    public function test_visible_category_is_searchable(): void
    {
        $category = Category::factory()->create(['is_visible' => true]);

        $this->assertTrue($category->shouldBeSearchable());
    }
}
