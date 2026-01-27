<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    /**
     * Get a setting value with caching.
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) return $default;

            switch ($setting->type) {
                case 'integer': return (int) $setting->value;
                case 'boolean': return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
                case 'json': return json_decode($setting->value, true);
                default: return $setting->value;
            }
        });
    }

    /**
     * Set a setting value and clear cache.
     */
    public static function set($key, $value)
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            $setting->update(['value' => (string) $value]);
            Cache::forget("setting_{$key}");
            return true;
        }
        return false;
    }
}
