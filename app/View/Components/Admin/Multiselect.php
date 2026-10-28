<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A searchable multiple choice: a checkbox per option, submitted as {name}[].
 * Without JS it is a plain checkbox list; admin.js adds the search filter and the
 * live "N selected" count. Nothing checked submits nothing, so read it with
 * $request->validated('{name}', []). Validation errors for {name} and {name}.N
 * show under the whole group (data-field-errors).
 *
 * <x-admin.multiselect name="categories" :label="__('...')" :options="[id => name]" :selected="[ids]" />
 */
class Multiselect extends Component
{
    /** @var array<int, array{value: string, label: string, checked: bool}> */
    public array $choices;

    public int $selectedCount;

    /**
     * @param  array<int|string, string>  $options  value => label
     * @param  array<int, int|string>  $selected
     */
    public function __construct(
        public string $name,
        public string $label,
        array $options = [],
        array $selected = [],
        public ?string $help = null,
        public ?string $empty = null,
    ) {
        $selected = array_map('strval', $selected);

        $this->choices = array_map(fn ($value, string $label) => [
            'value' => (string) $value,
            'label' => $label,
            'checked' => in_array((string) $value, $selected, true),
        ], array_keys($options), $options);

        $this->selectedCount = count(array_filter($this->choices, fn (array $choice) => $choice['checked']));
    }

    public function render(): View
    {
        return view('components.admin.multiselect');
    }
}
