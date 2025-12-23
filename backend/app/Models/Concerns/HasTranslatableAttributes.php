<?php

namespace App\Models\Concerns;

use Illuminate\Support\Arr;
use InvalidArgumentException;

trait HasTranslatableAttributes
{
    /**
     * Get the translated value for a translatable attribute.
     */
    public function getTranslation(string $attribute, ?string $locale = null, ?string $fallback = null): mixed
    {
        if (!\in_array($attribute, $this->translatable ?? [], true)) {
            return $this->getAttributeFromArray($attribute);
        }

        $translations = $this->getAttribute("{$attribute}_translations") ?? [];
        if (\is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        $locale ??= app()->getLocale() ?? config('i18n.default', config('app.locale'));
        $fallback ??= config('i18n.fallback', config('app.fallback_locale'));

        return Arr::get($translations, $locale)
            ?? Arr::get($translations, $fallback)
            ?? $this->getAttributeFromArray($attribute);
    }

    /**
     * Persist a translated value for a translatable attribute.
     */
    public function setTranslation(string $attribute, string $locale, mixed $value): static
    {
        if (!\in_array($attribute, $this->translatable ?? [], true)) {
            throw new InvalidArgumentException(sprintf('Attribute [%s] is not translatable on %s.', $attribute, static::class));
        }

        $translations = $this->getAttribute("{$attribute}_translations") ?? [];
        if (\is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        $translations[$locale] = $value;
        $this->setAttribute("{$attribute}_translations", $translations);

        return $this;
    }

    /**
     * Export attributes with translated values applied.
     */
    public function toLocalizedArray(?string $locale = null, ?string $fallback = null): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getTranslatableAttributes() as $attribute) {
            $attributes[$attribute] = $this->getTranslation($attribute, $locale, $fallback);
        }

        return $attributes;
    }

    /**
     * Override attribute retrieval to inject localized values transparently.
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (\in_array($key, $this->getTranslatableAttributes(), true)) {
            return $this->getTranslation($key);
        }

        return $value;
    }

    /**
     * Accessor for the list of translatable attributes.
     *
     * @return array<int, string>
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }
}
