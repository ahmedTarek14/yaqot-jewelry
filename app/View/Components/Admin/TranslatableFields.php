<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Translated form fields as one tab per locale (Arabic first), with the
 * {field}_{locale} input names the Form Requests expect.
 *
 * <x-admin.translatable-fields :model="$category" :fields="[
 *     'name' => ['label' => __('...'), 'required' => true, 'maxlength' => 255],
 *     'description' => ['label' => __('...'), 'type' => 'textarea', 'help' => __('...')],
 * ]" />
 *
 * admin.js switches to the tab of the first invalid field and flags every tab that has errors.
 */
class TranslatableFields extends Component
{
    /**
     * One entry per locale, each with its resolved inputs.
     *
     * @var array<int, array{code: string, native: string, dir: string, inputs: array<int, array<string, mixed>>}>
     */
    public array $tabs;

    /**
     * @param  array<string, array{label: string, type?: string, required?: bool, maxlength?: int, rows?: int, help?: string}>  $fields
     */
    public function __construct(
        ?Model $model,
        array $fields,
        public string $id = 'translations',
    ) {
        $supported = LaravelLocalization::getSupportedLocales();

        $this->tabs = array_map(fn (string $code) => [
            'code' => $code,
            'native' => $supported[$code]['native'] ?? $code,
            'dir' => ($supported[$code]['script'] ?? 'Latn') === 'Arab' ? 'rtl' : 'ltr',
            'inputs' => array_map(fn (string $field, array $options) => [
                'name' => "{$field}_{$code}",
                'value' => $model?->translate($code, false)?->{$field},
                'label' => $options['label'],
                'type' => $options['type'] ?? 'text',
                'required' => $options['required'] ?? false,
                'maxlength' => $options['maxlength'] ?? null,
                'rows' => $options['rows'] ?? 5,
                'help' => $options['help'] ?? null,
            ], array_keys($fields), $fields),
        ], config('translatable.locales'));
    }

    public function render(): View
    {
        return view('components.admin.translatable-fields');
    }
}
