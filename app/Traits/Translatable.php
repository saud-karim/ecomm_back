<?php

namespace App\Traits;

/**
 * Translatable Trait
 * ─────────────────
 * Automatically serves the correct language column based on app()->getLocale().
 *
 * Convention:
 *   - English content is stored in:  name_en, description_en, ...
 *   - Arabic content is stored in:   name_ar, description_ar, ...
 *
 * Usage in models:
 *   use App\Traits\Translatable;
 *
 *   protected array $translatable = ['name', 'description', 'short_description'];
 *
 * When you access $model->name the trait returns name_ar or name_en automatically.
 * Use $model->name_ar / $model->name_en to get a specific locale regardless.
 */
trait Translatable
{
    /**
     * Override getAttribute to auto-select locale column.
     */
    public function getAttribute($key): mixed
    {
        // If the key is in our translatable list, redirect to locale-specific column
        if ($this->isTranslatableAttribute($key)) {
            $locale  = app()->getLocale(); // 'ar' or 'en'
            $localeKey = $key . '_' . $locale;

            // Return locale-specific value; fall back to other locale if null
            $value = parent::getAttribute($localeKey);
            if ($value !== null && $value !== '') {
                return $value;
            }

            // Fallback: try the opposite locale
            $fallback = $locale === 'ar' ? 'en' : 'ar';
            return parent::getAttribute($key . '_' . $fallback);
        }

        return parent::getAttribute($key);
    }

    /**
     * When converting to array (API responses), replace translatable keys
     * with their resolved locale values and remove the raw _ar / _en columns.
     */
    public function toArray(): array
    {
        $array   = parent::toArray();
        $locales = ['ar', 'en'];

        foreach ($this->getTranslatableAttributes() as $field) {
            // Add the resolved value under the plain key
            $array[$field] = $this->getAttribute($field);

            // Remove the raw locale-specific columns from output
            foreach ($locales as $locale) {
                unset($array["{$field}_{$locale}"]);
            }
        }

        return $array;
    }

    /**
     * Get the list of translatable attributes.
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Check if a given attribute key is translatable (exact match, not _ar/_en variant).
     */
    protected function isTranslatableAttribute(string $key): bool
    {
        $translatables = $this->getTranslatableAttributes();
        if (!in_array($key, $translatables)) {
            return false;
        }
        // Make sure we're not overriding _ar / _en explicit access
        foreach (['ar', 'en'] as $locale) {
            if (str_ends_with($key, "_{$locale}")) {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper to get a specific locale value regardless of current locale.
     *
     * Usage: $model->getTranslation('name', 'ar')
     */
    public function getTranslation(string $field, string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        return parent::getAttribute("{$field}_{$locale}");
    }

    /**
     * Helper to set a translation value.
     *
     * Usage: $model->setTranslation('name', 'ar', 'قيمة')
     */
    public function setTranslation(string $field, string $locale, ?string $value): static
    {
        $this->setAttribute("{$field}_{$locale}", $value);
        return $this;
    }
}
