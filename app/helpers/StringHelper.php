<?php
namespace App\Helpers;
 class StringHelper
 {
    public static function normalize_string($string) {
        return strtolower(trim((string) $string));
    }
 }