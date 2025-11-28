<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AddonsService;

class AddonsController
{
    private AddonsService $addonsService;

    public function __construct(AddonsService $addonsService)
    {
        $this->addonsService = $addonsService;
    }

    /**
     * Get all addon categories
     */
    public function categories(Request $request): Response
    {
        $appliesTo = $request->get('applies_to', 'all');
        $categories = $this->addonsService->getCategories($appliesTo);

        return Response::json(['categories' => $categories]);
    }

    /**
     * Get addons for a vessel
     */
    public function forVessel(Request $request): Response
    {
        $vesselId = (int) $request->get('vessel_id');
        $vesselType = $request->get('vessel_type', 'yacht');
        $lang = $request->get('lang', 'en');

        if (!$vesselId) {
            return Response::json(['error' => 'vessel_id is required'], 400);
        }

        $addons = $this->addonsService->getForVessel($vesselId, $vesselType, $lang);

        return Response::json(['categories' => $addons]);
    }

    /**
     * Get addons for a tour
     */
    public function forTour(Request $request): Response
    {
        $tourId = (int) $request->get('tour_id');
        $tourCategory = $request->get('tour_category', 'islands');
        $lang = $request->get('lang', 'en');

        if (!$tourId) {
            return Response::json(['error' => 'tour_id is required'], 400);
        }

        $addons = $this->addonsService->getForTour($tourId, $tourCategory, $lang);

        return Response::json(['categories' => $addons]);
    }

    /**
     * Get popular addons
     */
    public function popular(Request $request): Response
    {
        $lang = $request->get('lang', 'en');
        $limit = min((int) $request->get('limit', 8), 20);

        $addons = $this->addonsService->getPopular($lang, $limit);

        return Response::json(['addons' => $addons]);
    }

    /**
     * Calculate total for selected addons
     */
    public function calculate(Request $request): Response
    {
        $data = $request->json();

        $selectedAddons = $data['addons'] ?? [];
        $guests = (int) ($data['guests'] ?? 2);
        $hours = (int) ($data['hours'] ?? 4);

        if (empty($selectedAddons)) {
            return Response::json([
                'total_thb' => 0,
                'breakdown' => []
            ]);
        }

        $result = $this->addonsService->calculateTotal($selectedAddons, $guests, $hours);

        return Response::json($result);
    }

    /**
     * Get recommended addons based on current selection
     */
    public function recommended(Request $request): Response
    {
        $selectedIds = explode(',', $request->get('selected', ''));
        $selectedIds = array_filter(array_map('intval', $selectedIds));
        $appliesTo = $request->get('applies_to', 'all');
        $lang = $request->get('lang', 'en');

        $addons = $this->addonsService->getRecommended($selectedIds, $appliesTo, $lang);

        return Response::json(['addons' => $addons]);
    }

    /**
     * Get single addon details
     */
    public function show(Request $request): Response
    {
        $id = (int) $request->get('id');
        $lang = $request->get('lang', 'en');

        $addon = $this->addonsService->getById($id, $lang);

        if (!$addon) {
            return Response::json(['error' => 'Addon not found'], 404);
        }

        return Response::json(['addon' => $addon]);
    }
}
