<?php
function convertRubToNgn($rubAmount) {
    static $cachedRate = null;
    if ($cachedRate === null) {
        $url = "https://api.exchangerate.host/latest?base=RUB&symbols=NGN";
        $res = @file_get_contents($url);
        if ($res) {
            $data = json_decode($res, true);
            $cachedRate = $data['rates']['NGN'] ?? 15;
        } else {
            $cachedRate = 15; // fallback
        }
    }
    return round($rubAmount * $cachedRate);
}