<?php

namespace App\Controller\Admin;

use App\Service\DashboardService;
use App\Service\DatabaseFactory;
use App\Twig\AeroGlideExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use PDO;

class DashboardController extends AbstractController
{
    private PDO $pdo;
    private DashboardService $dashboardService;
    private ?Environment $customTwig = null;

    public function __construct(
        ?PDO $pdo = null,
        ?DashboardService $dashboardService = null
    ) {
        $this->pdo = $pdo ?? DatabaseFactory::getConnection();
        $this->dashboardService = $dashboardService ?? new DashboardService($this->pdo);

        // Standalone Twig fallback for direct invocation without full Symfony kernel
        $templatesDir = dirname(__DIR__, 3) . '/templates';
        if (is_dir($templatesDir)) {
            $loader = new FilesystemLoader($templatesDir);
            $this->customTwig = new Environment($loader, [
                'auto_reload' => true,
                'strict_variables' => false,
            ]);
            $this->customTwig->addExtension(new AeroGlideExtension());
        }
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/admin/dashboard', name: 'admin_dashboard_alias', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $adminUser = function_exists('auth_get_user') ? auth_get_user() : ['name' => 'Administrator', 'username' => 'admin'];
        $data = $this->dashboardService->getDashboardData();

        if (function_exists('admin_log')) {
            admin_log('Viewed Dashboard');
        }

        $context = [
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
            'current_page' => 'index.php',
        ];

        // Prefer Symfony's containerized Twig if available; otherwise use custom loader
        if ($this->container && $this->container->has('twig')) {
            return $this->render('admin/dashboard.html.twig', $context);
        }

        $html = $this->customTwig->render('admin/dashboard.html.twig', $context);
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/admin/api/stats', name: 'admin_dashboard_api_stats', methods: ['GET'])]
    public function apiStats(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData();
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
