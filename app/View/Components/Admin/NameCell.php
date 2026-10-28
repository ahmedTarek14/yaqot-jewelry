<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * An index-table name: the current locale's name as the edit link, with the
 * other locale's name underneath, so a missing translation is visible at a glance.
 * The model's translations must be eager-loaded.
 */
class NameCell extends Component
{
    public ?string $primary;

    /** @var array<int, array{code: string, name: ?string}> */
    public array $others;

    /**
     * Without a $url (e.g. a trashed row that cannot be edited) the name is plain text.
     */
    public function __construct(Model $model, public ?string $url = null, string $attribute = 'name')
    {
        $current = app()->getLocale();

        $this->primary = $model->translate($current, false)?->{$attribute};

        $this->others = collect(config('translatable.locales'))
            ->reject(fn (string $code) => $code === $current)
            ->map(fn (string $code) => ['code' => $code, 'name' => $model->translate($code, false)?->{$attribute}])
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('components.admin.name-cell');
    }
}
