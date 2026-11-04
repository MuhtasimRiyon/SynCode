<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SiteSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = DB::table('site_settings')->where('key', $key)->value('value');

        return $value === null ? $default : json_decode($value, true);
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value)]);
    }
}
