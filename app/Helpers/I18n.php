<?php

namespace App\Helpers;

class I18n {
    protected static array $translations = [];
    protected static ?string $currentLocale = null;

    public static function getLocale(): string {
        if (self::$currentLocale !== null) {
            return self::$currentLocale;
        }

        Session::start();
        $locale = Session::get('locale');
        if (!$locale) {
            $user = AuthHelper::user();
            $locale = $user['locale'] ?? config('app.locale', 'en');
        }

        $available = array_keys(config('app.available_locales', ['en' => 'English']));
        if (!in_array($locale, $available)) {
            $locale = config('app.fallback_locale', 'en');
        }

        self::$currentLocale = $locale;
        return self::$currentLocale;
    }

    public static function setLocale(string $locale): void {
        $available = array_keys(config('app.available_locales', ['en' => 'English']));
        if (in_array($locale, $available)) {
            self::$currentLocale = $locale;
            Session::set('locale', $locale);
        }
    }

    public static function isRtl(): bool {
        return self::getLocale() === 'ar';
    }

    public static function translate(string $key, array $replace = []): string {
        $locale = self::getLocale();

        if (!isset(self::$translations[$locale])) {
            self::loadLocale($locale);
        }

        $text = self::$translations[$locale][$key] ?? null;

        // Fallback to English if translation is missing
        if ($text === null && $locale !== 'en') {
            if (!isset(self::$translations['en'])) {
                self::loadLocale('en');
            }
            $text = self::$translations['en'][$key] ?? $key;
        }

        if ($text === null) {
            $text = $key;
        }

        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string)$v, $text);
        }

        return $text;
    }

    protected static function loadLocale(string $locale): void {
        $path = __DIR__ . '/../lang/' . $locale . '.json';
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            self::$translations[$locale] = is_array($decoded) ? $decoded : [];
        } else {
            self::$translations[$locale] = [];
        }
    }
}
