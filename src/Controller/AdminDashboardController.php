<?php

namespace App\Controller;

use App\Service\DashboardService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Twig\TwigFilter;
use PDO;

class AdminDashboardController
{
    private PDO $pdo;
    private DashboardService $dashboardService;
    private Environment $twig;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->dashboardService = new DashboardService($pdo);

        $templatesDir = dirname(__DIR__, 2) . '/templates';
        $loader = new FilesystemLoader($templatesDir);
        $this->twig = new Environment($loader, [
            'auto_reload' => true,
            'strict_variables' => false,
        ]);

        // Register custom Twig helper functions and filters matching the application
        $this->twig->addFunction(new TwigFunction('ag_base_url', function (string $path = '') {
            if (function_exists('ag_base_url')) {
                return ag_base_url($path);
            }
            return '/' . ltrim($path, '/');
        }));

        $this->twig->addFunction(new TwigFunction('asset_find', function (array $terms) {
            if (function_exists('asset_find')) {
                return asset_find($terms);
            }
            return 'asset/logo.png';
        }));

        $this->twig->addFilter(new TwigFilter('peso', function ($amount) {
            return '₱' . number_format((float)$amount, 2);
        }));

        $this->twig->addFilter(new TwigFilter('peso_int', function ($amount) {
            return '₱' . number_format((float)$amount, 0);
        }));

        $this->twig->addFilter(new TwigFilter('format_date', function ($datetime) {
            if (!$datetime) return '—';
            return date('M j, Y — g:i A', strtotime($datetime));
        }));

        $this->twig->addFilter(new TwigFilter('format_date_short', function ($datetime) {
            if (!$datetime) return '—';
            return date('M j, Y', strtotime($datetime));
        }));
    }

    public function index(Request $request): Response
    {
        $adminUser = function_exists('auth_get_user') ? auth_get_user() : ['name' => 'Administrator', 'username' => 'admin'];
        $data = $this->dashboardService->getDashboardData();

        if (function_exists('admin_log')) {
            admin_log('Viewed Symfony Dashboard');
        }

        $html = $this->twig->render('admin/symfony_dashboard.html.twig', [
            'admin' => $adminUser,
            'summary' => $data['summary'],
            'modes' => $data['modes'],
            'monthly_trend' => $data['monthly_trend'],
            'top_destinations' => $data['top_destinations'],
            'airline_stats' => $data['airline_stats'],
            'recent_bookings' => $data['recent_bookings'],
            'recent_users' => $data['recent_users'],
            'recent_logs' => $data['recent_logs'],
            'upcoming_flights' => $data['upcoming_flights'],
            'generated_at' => $data['generated_at'],
            'current_page' => 'symfony_dashboard.php',
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function apiStats(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData();
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
