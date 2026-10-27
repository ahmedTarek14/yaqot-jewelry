<?php

namespace Tests\Unit;

use App\Support\AdminListing;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AdminListingTest extends TestCase
{
    private function listing(string $query = ''): AdminListing
    {
        return AdminListing::fromRequest(
            Request::create('http://localhost/en/admin/categories'.($query ? "?$query" : '')),
            ['name', 'sort_order', 'updated_at'],
            'sort_order',
            'asc',
            ['status' => ['active', 'inactive']],
        );
    }

    public function test_defaults_without_parameters(): void
    {
        $listing = $this->listing();

        $this->assertNull($listing->search);
        $this->assertSame('sort_order', $listing->sort);
        $this->assertSame('asc', $listing->direction);
        $this->assertSame([], $listing->filters);
        $this->assertFalse($listing->isFiltered());
        $this->assertSame([], $listing->sortParameters());
    }

    public function test_allowlisted_values_are_accepted(): void
    {
        $listing = $this->listing('q=%20ring%20&sort=name&dir=desc&status=inactive');

        $this->assertSame('ring', $listing->search);
        $this->assertSame('name', $listing->sort);
        $this->assertSame('desc', $listing->direction);
        $this->assertSame('inactive', $listing->filter('status'));
        $this->assertTrue($listing->isFiltered());
        $this->assertSame(['sort' => 'name', 'dir' => 'desc'], $listing->sortParameters());
    }

    public function test_values_outside_the_allowlists_are_ignored(): void
    {
        $listing = $this->listing('sort=password&dir=sideways&status=deleted&other=1');

        $this->assertSame('sort_order', $listing->sort);
        $this->assertSame('asc', $listing->direction);
        $this->assertNull($listing->filter('status'));
        $this->assertFalse($listing->isFiltered());
    }

    public function test_a_direction_alone_applies_to_the_default_column(): void
    {
        $listing = $this->listing('dir=desc');

        $this->assertSame(['sort_order', 'desc'], [$listing->sort, $listing->direction]);
    }

    public function test_a_rejected_column_also_drops_its_direction(): void
    {
        $listing = $this->listing('sort=password&dir=desc');

        $this->assertSame(['sort_order', 'asc'], [$listing->sort, $listing->direction]);
    }

    public function test_array_parameters_are_ignored(): void
    {
        $listing = $this->listing('q[]=a&sort[]=name&dir[]=desc&status[]=active');

        $this->assertNull($listing->search);
        $this->assertSame('sort_order', $listing->sort);
        $this->assertSame('asc', $listing->direction);
        $this->assertNull($listing->filter('status'));
    }

    public function test_the_search_term_is_trimmed_and_capped(): void
    {
        $this->assertNull($this->listing('q=%20%20')->search);
        $this->assertSame(100, mb_strlen($this->listing('q='.str_repeat('خ', 150))->search));
    }

    public function test_like_wildcards_in_the_search_term_are_escaped(): void
    {
        $this->assertSame('%100!% gold!_ring!!%', $this->listing('q='.rawurlencode('100% gold_ring!'))->likePattern());
        $this->assertNull($this->listing()->likePattern());
    }

    public function test_sort_urls_toggle_the_direction_and_keep_filters_but_not_the_page(): void
    {
        $listing = $this->listing('q=ring&status=active&page=3');

        $this->assertSame('http://localhost/en/admin/categories?q=ring&status=active&sort=name&dir=asc', $listing->sortUrl('name'));
        // The default sort column, already ascending, flips to descending.
        $this->assertSame('http://localhost/en/admin/categories?q=ring&status=active&sort=sort_order&dir=desc', $listing->sortUrl('sort_order'));

        $sorted = $this->listing('sort=name&dir=asc');
        $this->assertSame('http://localhost/en/admin/categories?sort=name&dir=desc', $sorted->sortUrl('name'));

        // Going back to the default sort drops the parameters altogether.
        $this->assertSame('http://localhost/en/admin/categories', $this->listing('sort=sort_order&dir=desc')->sortUrl('sort_order'));
    }

    public function test_aria_sort_describes_the_current_column_only(): void
    {
        $listing = $this->listing('sort=name&dir=desc');

        $this->assertSame('descending', $listing->ariaSort('name'));
        $this->assertSame('none', $listing->ariaSort('sort_order'));
        $this->assertSame('ascending', $this->listing()->ariaSort('sort_order'));
    }

    public function test_reset_url_drops_every_parameter(): void
    {
        $this->assertSame('http://localhost/en/admin/categories', $this->listing('q=a&status=active&sort=name')->resetUrl());
    }
}
