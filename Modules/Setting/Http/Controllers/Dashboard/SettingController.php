<?php

namespace Modules\Setting\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\EgyptianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Modules\Collection\Models\Collection;
use Modules\Setting\Http\Requests\SettingRequest;
use Modules\Setting\Services\SettingService;

class SettingController extends Controller
{
    public function edit(SettingService $settings): View
    {
        return view('setting::dashboard.edit', [
            'values' => $settings->formValues(),
            'heroSlides' => SettingRequest::heroSlides(),
            'collections' => Collection::query()->where('is_active', true)->with('translations')->ordered()->get()
                ->mapWithKeys(fn (Collection $collection) => [$collection->id => (string) $collection->name])
                ->all(),
        ]);
    }

    public function update(SettingRequest $request, SettingService $settings): JsonResponse
    {
        $settings->setMany($this->values($request));

        return $this->savedResponse(route('admin.settings.edit'));
    }

    /**
     * Every field of the form, ready for SettingService::setMany(). An empty field clears its setting.
     *
     * @return array<string, mixed>
     */
    private function values(SettingRequest $request): array
    {
        $values = [
            'whatsapp_number' => EgyptianPhone::normalizeMobile($request->validated('whatsapp_number')),
            'phone' => EgyptianPhone::normalizePhone($request->validated('phone')),
            'email' => $request->validated('email'),
            'prominent_collection' => $request->validated('prominent_collection'),
            'ga_measurement_id' => $request->validated('ga_measurement_id'),
        ];

        foreach (SettingRequest::URLS as $key) {
            $values[$key] = $request->validated($key);
        }

        foreach (SettingRequest::heroSlides() as $slide) {
            $values["hero_{$slide}_cta_url"] = $request->validated("hero_{$slide}_cta_url");
            // Only a submitted switch is written, so a request without the field leaves the slide as it was.
            if ($request->validated("hero_{$slide}_enabled") !== null) {
                $values["hero_{$slide}_enabled"] = $request->boolean("hero_{$slide}_enabled") ? '1' : '0';
            }
        }

        foreach (array_keys(SettingRequest::translatedKeys()) as $key) {
            foreach (config('translatable.locales') as $locale) {
                $values[$key][$locale] = $request->validated("{$key}_{$locale}");
            }
        }

        return $values;
    }
}
