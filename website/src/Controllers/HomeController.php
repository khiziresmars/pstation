<?php

declare(strict_types=1);

namespace Website\Controllers;

use App\Services\VesselService;
use App\Services\TourService;

/**
 * Home page controller
 */
class HomeController extends BaseController
{
    public function index(array $params): void
    {
        // SEO
        $this->seo
            ->title('Premium Yacht Rentals & Island Tours')
            ->description('Explore Phuket by yacht or speedboat. Book private charters, island tours to Phi Phi, James Bond Island, and more. Best prices guaranteed.')
            ->keywords('phuket yacht rental, boat charter phuket, phi phi island tour, james bond island, speedboat phuket')
            ->canonical('/')
            ->localBusiness();

        // Fetch featured vessels and tours (cached)
        $vesselService = new VesselService();
        $tourService = new TourService();

        $vessels = $this->cache('featured_vessels', 3600, function () use ($vesselService) {
            return $vesselService->getFeatured(6);
        });

        $tours = $this->cache('featured_tours', 3600, function () use ($tourService) {
            return $tourService->getFeatured(6);
        });

        // Get reviews/testimonials
        $reviews = $this->cache('home_reviews', 3600, function () {
            return $this->db->query("
                SELECT r.*, u.first_name, u.last_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'approved' AND r.rating >= 4
                ORDER BY r.created_at DESC
                LIMIT 6
            ");
        });

        $this->render('home/index', [
            'vessels' => $vessels,
            'tours' => $tours,
            'reviews' => $reviews,
        ]);
    }
}
