<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\Carbon;

class SystemFormatService
{
    /**
     * Get the active system-wide date format (default: 'M d, Y').
     */
    public static function dateFormat(): string
    {
        return SystemSetting::get('date_format', 'M d, Y');
    }

    /**
     * Get the active system-wide time format (default: 'h:i A').
     */
    public static function timeFormat(): string
    {
        return SystemSetting::get('time_format', 'h:i A');
    }

    /**
     * Check if the active time format is 24-hour format.
     */
    public static function is24Hour(): bool
    {
        return str_contains(static::timeFormat(), 'H');
    }

    /**
     * Get a human-readable description of the time format mode ('12-hour' or '24-hour').
     */
    public static function timeFormatType(): string
    {
        return static::is24Hour() ? '24-hour' : '12-hour';
    }

    /**
     * Format a date string or Carbon instance using system-wide date format.
     */
    public static function formatDate(mixed $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            return Carbon::parse($date)->setTimezone('Asia/Manila')->format(static::dateFormat());
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    /**
     * Format a time string or Carbon instance using system-wide time format.
     */
    public static function formatTime(mixed $time): string
    {
        if (empty($time)) {
            return 'N/A';
        }

        try {
            return Carbon::parse($time)->setTimezone('Asia/Manila')->format(static::timeFormat());
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }

    /**
     * Format a datetime string or Carbon instance using system-wide date and time formats.
     */
    public static function formatDateTime(mixed $datetime): string
    {
        if (empty($datetime)) {
            return 'N/A';
        }

        try {
            $format = static::dateFormat().' '.static::timeFormat();

            return Carbon::parse($datetime)->setTimezone('Asia/Manila')->format($format);
        } catch (\Throwable $e) {
            return (string) $datetime;
        }
    }
}
