<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group'
    ];

    /**
     * Accessor untuk mengkonversi value berdasarkan type
     */
    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return unserialize($value);
            case 'datetime':
                return $value ? \Carbon\Carbon::parse($value) : null;
            case 'date':
                return $value ? \Carbon\Carbon::parse($value)->toDateString() : null;
            default:
                return $value;
        }
    }

    /**
     * Mutator untuk menyimpan value berdasarkan type
     */
    public function setValueAttribute($value)
    {
        // Handle NULL values - convert to empty string
        if ($value === null) {
            $this->attributes['value'] = '';
            return;
        }

        switch ($this->type) {
            case 'boolean':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            case 'array':
                $this->attributes['value'] = serialize($value);
                break;
            case 'datetime':
            case 'date':
                $this->attributes['value'] = $value instanceof \Carbon\Carbon ? 
                    $value->toDateTimeString() : $value;
                break;
            default:
                $this->attributes['value'] = (string) $value;
        }
    }

    /**
     * Dapatkan setting berdasarkan key
     */
    public static function get($key, $default = null, $useCache = true)
    {
        if ($useCache) {
            return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        }

        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set atau update setting
     */
    public static function set($key, $value, $type = 'string', $description = null, $group = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'group' => $group ?? 'general'
            ]
        );

        // Hapus cache
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
        if ($setting->group) {
            Cache::forget("settings.group.{$setting->group}");
        }

        return $setting;
    }

    /**
     * Dapatkan semua setting berdasarkan group
     */
    public static function getByGroup($group, $useCache = true)
    {
        if ($useCache) {
            return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
                return self::where('group', $group)->get()->keyBy('key');
            });
        }

        return self::where('group', $group)->get()->keyBy('key');
    }

    /**
     * Dapatkan semua setting dalam format key-value array
     */
    public static function getAllAsArray($useCache = true)
    {
        if ($useCache) {
            return Cache::remember('settings.all', 3600, function () {
                return self::all()->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->value];
                })->toArray();
            });
        }

        return self::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value];
        })->toArray();
    }

    /**
     * Flush semua cache setting
     */
    public static function flushCache()
    {
        $groups = self::distinct()->pluck('group');
        
        Cache::forget('settings.all');
        
        foreach ($groups as $group) {
            if ($group) {
                Cache::forget("settings.group.{$group}");
            }
        }

        // Hapus cache individual settings
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
    }

    /**
     * Boot model
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache saat setting berubah
        static::saved(function ($setting) {
            self::flushCache();
        });

        static::deleted(function ($setting) {
            self::flushCache();
        });
    }

    /**
     * Scope untuk setting berdasarkan group
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }
}