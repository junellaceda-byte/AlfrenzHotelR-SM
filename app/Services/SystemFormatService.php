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
     * Map a PHP date format string to a human-readable format label (e.g. 'M d, Y' -> 'MMM DD, YYYY').
     */
    public static function displayDateFormat(?string $format = null): string
    {
        $activeFormat = $format ?? static::dateFormat();

        return match ($activeFormat) {
            'M d, Y' => 'MMM DD, YYYY',
            'F d, Y' => 'MMMM DD, YYYY',
            'F j, Y' => 'MMMM D, YYYY',
            'Y-m-d' => 'YYYY-MM-DD',
            'm/d/Y' => 'MM/DD/YYYY',
            'd/m/Y' => 'DD/MM/YYYY',
            default => $activeFormat,
        };
    }

    /**
     * Map a PHP time format string to a human-readable format label (e.g. 'h:i A' -> 'HH:MM').
     */
    public static function displayTimeFormat(?string $format = null): string
    {
        $activeFormat = $format ?? static::timeFormat();

        return match ($activeFormat) {
            'h:i A', 'H:i' => 'HH:MM',
            'h:i:s A', 'H:i:s' => 'HH:MM:SS',
            default => $activeFormat,
        };
    }

    /**
     * Get the human-readable time format mode label for Receptionist Settings.
     */
    public static function timeFormatModeLabel(?string $format = null): string
    {
        $activeFormat = $format ?? static::timeFormat();

        return str_contains($activeFormat, 'H') ? '24-Hour Standard Time' : 'HH:MM AM/PM';
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
