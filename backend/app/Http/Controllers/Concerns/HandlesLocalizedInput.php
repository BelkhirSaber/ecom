<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesLocalizedInput
{
    protected function getRequestLocale(Request $request): string
    {
        return (string) $request->attributes->get(
            'locale',
            config('i18n.default', config('app.locale', 'fr'))
        );
    }

    protected function getDefaultLocale(): string
    {
        return (string) config('i18n.default', config('app.locale', 'fr'));
    }

    /**
     * Mutate the validated payload so translatable fields target *_translations when needed.
     *
     * @param  array<int, string>  $translatable
     */
    protected function applyLocalizedInput(Request $request, array $data, array $translatable, ?Model $model = null): array
    {
        $locale = $this->getRequestLocale($request);
        $defaultLocale = $this->getDefaultLocale();

        foreach ($translatable as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            $translationsKey = "{$field}_translations";

            $existingTranslations = [];

            if ($model && $model->{$translationsKey}) {
                $existingTranslations = (array) $model->{$translationsKey};
            } elseif (isset($data[$translationsKey]) && is_array($data[$translationsKey])) {
                $existingTranslations = $data[$translationsKey];
            }

            if ($locale !== $defaultLocale) {
                unset($data[$field]);
            }

            $shouldRemove = $value === null
                || $value === ''
                || (is_array($value) && count($value) === 0);

            if ($shouldRemove) {
                unset($existingTranslations[$locale]);
            } else {
                $existingTranslations[$locale] = $value;
            }

            $data[$translationsKey] = $existingTranslations;
        }

        return $data;
    }
}
