<?php

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount): string
    {
        return '$' . number_format((float)$amount, 2, '.', ',');
    }
}
