<?php

if (!function_exists('fy_label')) {
    /**
     * Returns "2024-2025" from start year 2024.
     */
    function fy_label(int $startYear): string
    {
        return $startYear . '-' . ($startYear + 1);
    }
}

if (!function_exists('fy_start')) {
    /**
     * Returns the financial year start year for the current date.
     * April = start of new FY.
     */
    function fy_start(): int
    {
        $month = (int) date('n');
        return $month >= 4 ? (int) date('Y') : (int) date('Y') - 1;
    }
}

if (!function_exists('fy_years')) {
    /**
     * Returns an array of [start_year => label] for a range of FYs.
     */
    function fy_years(int $from = 2020, int $count = 8): array
    {
        $years = [];
        $current = fy_start();
        $top = $current + 1;
        for ($y = $top; $y >= $from; $y--) {
            $years[$y] = fy_label($y);
        }
        return $years;
    }
}
