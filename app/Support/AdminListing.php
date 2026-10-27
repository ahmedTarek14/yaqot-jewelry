<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The query parameters of an admin index page, validated against allowlists:
 *   q          search term (trimmed, at most 100 characters)
 *   sort, dir  an allowlisted column and asc|desc
 *   {filter}   each filter accepts only its listed values
 * Anything else is ignored, so a hand-edited URL can never reach the query.
 */
final class AdminListing
{
    private const MAX_SEARCH_LENGTH = 100;

    /**
     * @param  array<string, string>  $filters  name => accepted value
     */
    private function __construct(
        private readonly Request $request,
        public readonly ?string $search,
        public readonly string $sort,
        public readonly string $direction,
        public readonly array $filters,
        private readonly string $defaultSort,
        private readonly string $defaultDirection,
    ) {}

    /**
     * @param  array<int, string>  $sortable  columns the page may sort by
     * @param  array<string, array<int, string>>  $filters  filter name => accepted values
     */
    public static function fromRequest(
        Request $request,
        array $sortable,
        string $defaultSort,
        string $defaultDirection = 'asc',
        array $filters = [],
    ): self {
        $search = $request->query('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, self::MAX_SEARCH_LENGTH) : '';

        // A rejected sort column falls back to the default sort as a whole, direction included.
        $sort = $request->query('sort', $defaultSort);
        $direction = $request->query('dir');

        if (! is_string($sort) || ! in_array($sort, $sortable, true)) {
            $sort = $defaultSort;
            $direction = $defaultDirection;
        }

        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : $defaultDirection;

        $active = [];
        foreach ($filters as $name => $values) {
            $value = $request->query($name);

            if (is_string($value) && in_array($value, $values, true)) {
                $active[$name] = $value;
            }
        }

        return new self($request, $search === '' ? null : $search, $sort, $direction, $active, $defaultSort, $defaultDirection);
    }

    public function filter(string $name): ?string
    {
        return $this->filters[$name] ?? null;
    }

    public function isFiltered(): bool
    {
        return $this->search !== null || $this->filters !== [];
    }

    /**
     * The search term as a LIKE pattern, with % and _ matched literally.
     * Use with: whereRaw("column like ? escape '!'", [$listing->likePattern()]).
     */
    public function likePattern(): ?string
    {
        if ($this->search === null) {
            return null;
        }

        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $this->search).'%';
    }

    /**
     * The current page sorted by $column: ascending first, or reversed when it is already the sort column.
     * Filters and the search term are kept; the page number resets.
     */
    public function sortUrl(string $column): string
    {
        $direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';

        $query = array_merge($this->request->query(), ['sort' => $column, 'dir' => $direction]);
        unset($query['page']);

        if ($column === $this->defaultSort && $direction === $this->defaultDirection) {
            unset($query['sort'], $query['dir']);
        }

        return $this->request->url().($query ? '?'.http_build_query($query) : '');
    }

    /**
     * The aria-sort value for a column header.
     */
    public function ariaSort(string $column): string
    {
        if ($this->sort !== $column) {
            return 'none';
        }

        return $this->direction === 'asc' ? 'ascending' : 'descending';
    }

    /**
     * The sort as query parameters, or none when it is the page's default.
     *
     * @return array<string, string>
     */
    public function sortParameters(): array
    {
        if ($this->sort === $this->defaultSort && $this->direction === $this->defaultDirection) {
            return [];
        }

        return ['sort' => $this->sort, 'dir' => $this->direction];
    }

    public function resetUrl(): string
    {
        return $this->request->url();
    }
}
