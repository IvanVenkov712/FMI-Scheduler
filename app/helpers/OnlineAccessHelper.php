<?php
namespace App\Helpers;

use App\Models\OnlineAccess;

class OnlineAccessHelper
{
    public static function build($platform, $meeting_link, $access_key = null, $host_notes = '', $generate_for_blank_key = false)
    {
        $online_access = new OnlineAccess();
        $online_access->platform = trim((string) $platform);
        $online_access->meeting_link = trim((string) $meeting_link);
        $online_access->access_key = self::resolve_access_key($access_key, $generate_for_blank_key);
        $online_access->host_notes = trim((string) $host_notes);

        return $online_access;
    }

    public static function build_from_payload($payload, $generate_for_blank_key = false)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        return self::build(
            $payload['platform'] ?? '',
            $payload['meeting_link'] ?? ($payload['link'] ?? ''),
            $payload['access_key'] ?? null,
            $payload['host_notes'] ?? '',
            $generate_for_blank_key
        );
    }

    public static function is_valid($online_access)
    {
        $online_access = self::build_from_payload($online_access);

        if ($online_access === null) {
            return false;
        }

        if (StringHelper::normalize_string($online_access->platform ?? '') === '') {
            return false;
        }

        $meeting_link = trim((string) ($online_access->meeting_link ?? ''));
        if ($meeting_link === '') {
            return false;
        }

        return filter_var($meeting_link, FILTER_VALIDATE_URL) !== false;
    }

    public static function generate_access_key()
    {
        return bin2hex(random_bytes(16));
    }

    private static function resolve_access_key($access_key, $generate_for_blank_key)
    {
        if ($access_key === null || ($generate_for_blank_key && trim((string) $access_key) === '')) {
            return self::generate_access_key();
        }

        return trim((string) $access_key);
    }
}
