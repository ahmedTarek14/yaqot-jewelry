<?php

namespace Modules\Setting\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\EgyptianPhone;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Setting\Http\Requests\SettingRequest;
use Modules\Setting\Services\SettingService;

class SettingController extends Controller
{
    /**
     * Folder (and ImageUploader type) of the homepage hero image.
     */
    private const IMAGE_DIRECTORY = 'settings';

    public function edit(SettingService $settings): View
    {
        $values = $settings->formValues();

        return view('setting::dashboard.edit', [
            'values' => $values,
            'heroImageUrl' => $values['hero_image'] ? Storage::disk('public')->url($values['hero_image']) : null,
        ]);
    }

    /**
     * The new hero image (if any) is stored first; ImageUploader removes it again if
     * the save fails. The replaced or removed file is deleted only after the save.
     */
    public function update(SettingRequest $request, SettingService $settings, ImageUploader $images): JsonResponse
    {
        $old = $settings->formValues()['hero_image'];
        $values = $this->values($request);

        $new = $images->storeWhile($request->file('hero_image'), self::IMAGE_DIRECTORY, function (?string $path) use ($request, $settings, $values, $old) {
            if ($path !== null) {
                $values['hero_image'] = $path;
            } elseif ($request->boolean('remove_hero_image')) {
                $values['hero_image'] = null;
            }

            $settings->setMany($values);

            return array_key_exists('hero_image', $values) ? $values['hero_image'] : $old;
        });

        // Only files this page stored are ever deleted.
        if ($old !== null && $old !== $new && str_starts_with($old, self::IMAGE_DIRECTORY.'/')) {
            $images->delete($old);
        }

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
        ];

        foreach (SettingRequest::URLS as $key) {
            $values[$key] = $request->validated($key);
        }

        foreach (array_keys(SettingRequest::TRANSLATED) as $key) {
            foreach (config('translatable.locales') as $locale) {
                $values[$key][$locale] = $request->validated("{$key}_{$locale}");
            }
        }

        return $values;
    }
}
