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
        ?DashboardService $dashboardService = null,
        ?Environment $twig = null,
        ?PDO $pdo = null
    ) {
        $this->pdo = $pdo ?? DatabaseFactory::getConnection();
        $this->dashboardService = $dashboardService ?? new DashboardService($this->pdo);
        $this->customTwig = $twig;
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/admin/', name: 'admin_dashboard_slash', methods: ['GET'])]
    #[Route('/admin/dashboard', name: 'admin_dashboard_alias', methods: ['GET'])]
    #[Route('/admin/index.php', name: 'admin_dashboard_legacy', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // 1. Session & Access Guard Check
        if (session_status() === PHP_SESSION_NONE) {
            @session_start(['read_and_close' => true]);
        }
        
        $authHelper = dirname(__DIR__, 3) . '/user/auth_helper.php';
        if (file_exists($authHelper)) {
            require_once $authHelper;
        }

        if (function_exists('auth_is_logged_in')) {
            if (!auth_is_logged_in()) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                $loginUrl = function_exists('ag_base_url')
                    ? ag_base_url('user/login.php?error=' . urlencode('Admin access only. Please log in with an administrator account.'))
                    : '/user/login.php';
                return $this->redirect($loginUrl);
            }
            if (function_exists('auth_is_staff_only') && auth_is_staff_only()) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                $staffTarget = function_exists('ag_base_url') ? ag_base_url('admin/bookings.php') : '/admin/bookings.php';
                return $this->redirect($staffTarget);
            }
            if (function_exists('auth_is_admin') && !auth_is_admin()) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                $loginUrl = function_exists('ag_base_url')
                    ? ag_base_url('user/login.php?error=' . urlencode('Admin access only. Please log in with an administrator account.'))
                    : '/user/login.php';
                return $this->redirect($loginUrl);
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $adminUser = function_exists('auth_get_user') ? (auth_get_user() ?? ['name' => 'Administrator', 'username' => 'admin']) : ['name' => 'Administrator', 'username' => 'admin'];
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

        // 2. Safe rendering: use Symfony container render when initialized; fallback cleanly if standalone
        if (isset($this->container) && $this->container->has('twig')) {
            return $this->render('admin/dashboard.html.twig', $context);
        }

        if ($this->customTwig !== null) {
            return new Response($this->customTwig->render('admin/dashboard.html.twig', $context), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $templatesDir = dirname(__DIR__, 3) . '/templates';
        $loader = new FilesystemLoader($templatesDir);
        $standaloneTwig = new Environment($loader, [
            'auto_reload' => true,
            'strict_variables' => false,
        ]);
        $standaloneTwig->addExtension(new AeroGlideExtension());
        
        return new Response($standaloneTwig->render('admin/dashboard.html.twig', $context), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
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
