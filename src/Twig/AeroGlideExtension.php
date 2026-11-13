<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AeroGlideExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ag_base_url', function (string $path = '') {
                if (function_exists('ag_base_url')) {
                    return ag_base_url($path);
                }
                return '/' . ltrim($path, '/');
            }),
            new TwigFunction('asset_find', function (array $terms) {
                if (function_exists('asset_find')) {
                    return asset_find($terms);
                }
                return 'asset/logo.png';
            }),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('peso', function ($amount) {
                return '₱' . number_format((float)$amount, 2);
            }),
            new TwigFilter('peso_int', function ($amount) {
                return '₱' . number_format((float)$amount, 0);
            }),
            new TwigFilter('format_date', function ($datetime) {
                if (!$datetime) return '—';
                return date('M j, Y — g:i A', strtotime($datetime));
            }),
            new TwigFilter('format_date_short', function ($datetime) {
                if (!$datetime) return '—';
                return date('M j, Y', strtotime($datetime));
            }),
        ];
    }
}
