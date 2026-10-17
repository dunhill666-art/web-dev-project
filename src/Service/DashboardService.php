<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\BookingRepository;
use App\Repository\FlightRepository;
use App\Repository\AdminLogRepository;
use PDO;

class DashboardService
{
    private UserRepository $userRepo;
    private BookingRepository $bookingRepo;
    private FlightRepository $flightRepo;
    private AdminLogRepository $logRepo;

    public function __construct(PDO $pdo)
    {
        $this->userRepo = new UserRepository($pdo);
        $this->bookingRepo = new BookingRepository($pdo);
        $this->flightRepo = new FlightRepository($pdo);
        $this->logRepo = new AdminLogRepository($pdo);
    }

    public function getDashboardData(): array
    {
        $totalBookings = $this->bookingRepo->countTotalBookings();
        $statusCounts = $this->bookingRepo->getCountsByStatus();
        $confirmedCount = $statusCounts['confirmed']['count'] ?? 0;
        $pendingCancelCount = $statusCounts['pending_cancellation']['count'] ?? 0;
        $cancelledCount = $statusCounts['cancelled']['count'] ?? 0;
        $pendingCount = $statusCounts['pending']['count'] ?? 0;

        $cancellationRate = $totalBookings > 0 ? round(($cancelledCount / $totalBookings) * 100, 1) : 0.0;

        $totalRevenue = $this->bookingRepo->getTotalRevenue();
        $avgBooking = $this->bookingRepo->getAverageBookingValue();
        $totalDiscounts = $this->bookingRepo->getTotalDiscounts();
        $totalRefunds = $this->bookingRepo->getTotalRefunds();

        $totalUsers = $this->userRepo->countTotalUsers();
        $newUsersMonth = $this->userRepo->countNewUsersThisMonth();

        $totalFlights = $this->flightRepo->countTotalFlights();
        $availableFlights = $this->flightRepo->countAvailableFlights();
        $totalSeats = $this->flightRepo->getTotalSeatsAvailable();
        $uniqueRoutes = $this->flightRepo->countUniqueRoutes();

        // Mode breakdown with percentages and visual indicators
        $rawModes = $this->bookingRepo->getModeBreakdown();
        $modeTotalCnt = array_sum(array_column($rawModes, 'cnt')) ?: 1;
        $modeColors = ['flights' => '#0d6efd', 'hotels' => '#16a34a', 'packages' => '#7c3aed'];
        $modeIcons  = ['flights' => '✈️', 'hotels' => '🏨', 'packages' => '🎁'];

        $modes = [];
        foreach ($rawModes as $m) {
            $key = strtolower($m['mode']);
            $cnt = (int)$m['cnt'];
            $pct = round(($cnt / $modeTotalCnt) * 100);
            $modes[] = [
                'mode' => $key,
                'label' => ucfirst($key),
                'count' => $cnt,
                'revenue' => (float)$m['rev'],
                'percentage' => $pct,
                'color' => $modeColors[$key] ?? '#64748b',
                'icon' => $modeIcons[$key] ?? '📦',
            ];
        }

        // Monthly trends
        $monthlyRaw = $this->bookingRepo->getMonthlyRevenueTrend(12);
        $maxRev = 1;
        foreach ($monthlyRaw as $mr) {
            if ((float)$mr['confirmed_revenue'] > $maxRev) {
                $maxRev = (float)$mr['confirmed_revenue'];
            }
        }
        $monthlyTrend = [];
        foreach ($monthlyRaw as $mr) {
            $rev = (float)$mr['confirmed_revenue'];
            $monthlyTrend[] = [
                'month' => $mr['month'],
                'short_label' => date('M', strtotime($mr['month'] . '-01')),
                'booking_count' => (int)$mr['booking_count'],
                'confirmed_revenue' => $rev,
                'gross_sales' => (float)$mr['gross_sales'],
                'discounts' => (float)$mr['discounts'],
                'bar_height_pct' => max(6, round(($rev / $maxRev) * 100)),
            ];
        }

        // Top destinations
        $topDestsRaw = $this->bookingRepo->getTopDestinations(6);
        $maxDestRev = 1;
        foreach ($topDestsRaw as $td) {
            if ((float)$td['rev'] > $maxDestRev) {
                $maxDestRev = (float)$td['rev'];
            }
        }
        $topDestinations = [];
        foreach ($topDestsRaw as $td) {
            $rev = (float)$td['rev'];
            $topDestinations[] = [
                'city' => $td['city'],
                'count' => (int)$td['cnt'],
                'revenue' => $rev,
                'percentage' => max(8, round(($rev / $maxDestRev) * 100)),
            ];
        }

        // Airline stats
        $airlineStats = $this->flightRepo->getAirlineStats();

        // Recent entities
        $recentBookings = $this->bookingRepo->findRecentBookings(10);
        $recentUsers = $this->userRepo->findRecentUsers(6);
        $recentLogs = $this->logRepo->findRecentLogs(8);
        $upcomingFlights = $this->flightRepo->findUpcomingFlights(6);

        return [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => '₱' . number_format($totalRevenue, 2),
                'total_bookings' => $totalBookings,
                'confirmed_bookings' => $confirmedCount,
                'pending_cancellations' => $pendingCancelCount,
                'cancelled_bookings' => $cancelledCount,
                'pending_bookings' => $pendingCount,
                'cancellation_rate' => $cancellationRate,
                'total_users' => $totalUsers,
                'new_users_month' => $newUsersMonth,
                'total_flights' => $totalFlights,
                'available_flights' => $availableFlights,
                'total_seats_available' => $totalSeats,
                'unique_routes' => $uniqueRoutes,
                'avg_booking_value' => $avgBooking,
                'total_discounts' => $totalDiscounts,
                'total_refunds' => $totalRefunds,
            ],
            'modes' => $modes,
            'monthly_trend' => $monthlyTrend,
            'top_destinations' => $topDestinations,
            'airline_stats' => $airlineStats,
            'recent_bookings' => $recentBookings,
            'recent_users' => $recentUsers,
            'recent_logs' => $recentLogs,
            'upcoming_flights' => $upcomingFlights,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
