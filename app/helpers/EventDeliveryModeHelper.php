<?php
namespace App\Helpers;

class EventDeliveryModeHelper
{
    public static function normalize($delivery_mode)
    {
        return StringHelper::normalize_string($delivery_mode);
    }

    public static function is_valid($delivery_mode)
    {
        return in_array(self::normalize($delivery_mode), ['physical', 'online', 'hybrid'], true);
    }

    public static function is_online($delivery_mode)
    {
        return self::normalize($delivery_mode) === 'online';
    }

    public static function requires_room($delivery_mode)
    {
        return in_array(self::normalize($delivery_mode), ['physical', 'hybrid'], true);
    }

    public static function supports_online_access($delivery_mode)
    {
        return in_array(self::normalize($delivery_mode), ['online', 'hybrid'], true);
    }

    public static function online_access_matches($delivery_mode, $online_access)
    {
        $delivery_mode = self::normalize($delivery_mode);

        if ($delivery_mode === 'physical') {
            return $online_access === null;
        }

        if (!self::supports_online_access($delivery_mode)) {
            return false;
        }

        return $online_access !== null;
    }

    public static function is_room_type_compatible($delivery_mode, $room_type)
    {
        if (!self::requires_room($delivery_mode)) {
            return false;
        }

        return in_array(StringHelper::normalize_string($room_type), ['physical', 'hybrid'], true);
    }
}
