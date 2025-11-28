<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PackagesService;

class PackagesController
{
    private PackagesService $packagesService;

    public function __construct(PackagesService $packagesService)
    {
        $this->packagesService = $packagesService;
    }

    /**
     * Get all packages
     */
    public function index(Request $request): Response
    {
        $lang = $request->get('lang', 'en');
        $type = $request->get('type');

        if ($type) {
            $packages = $this->packagesService->getByType($type, $lang);
        } else {
            $packages = $this->packagesService->getAll($lang);
        }

        return Response::json(['packages' => $packages]);
    }

    /**
     * Get featured packages
     */
    public function featured(Request $request): Response
    {
        $lang = $request->get('lang', 'en');
        $limit = min((int) $request->get('limit', 4), 10);

        $packages = $this->packagesService->getFeatured($lang, $limit);

        return Response::json(['packages' => $packages]);
    }

    /**
     * Get package by slug
     */
    public function show(Request $request): Response
    {
        $slug = $request->get('slug');
        $lang = $request->get('lang', 'en');

        if (!$slug) {
            return Response::json(['error' => 'slug is required'], 400);
        }

        $package = $this->packagesService->getBySlug($slug, $lang);

        if (!$package) {
            return Response::json(['error' => 'Package not found'], 404);
        }

        return Response::json(['package' => $package]);
    }

    /**
     * Calculate package price
     */
    public function calculate(Request $request): Response
    {
        $data = $request->json();

        $packageId = (int) ($data['package_id'] ?? 0);

        if (!$packageId) {
            return Response::json(['error' => 'package_id is required'], 400);
        }

        $options = [
            'guests' => (int) ($data['guests'] ?? 2),
            'hours' => (int) ($data['hours'] ?? 4),
            'base_id' => $data['base_id'] ?? null,
            'extra_addons' => $data['extra_addons'] ?? []
        ];

        $result = $this->packagesService->calculatePrice($packageId, $options);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * Get compatible vessels for package
     */
    public function vessels(Request $request): Response
    {
        $packageId = (int) $request->get('package_id');

        if (!$packageId) {
            return Response::json(['error' => 'package_id is required'], 400);
        }

        $vessels = $this->packagesService->getCompatibleVessels($packageId);

        return Response::json(['vessels' => $vessels]);
    }

    /**
     * Get compatible tours for package
     */
    public function tours(Request $request): Response
    {
        $packageId = (int) $request->get('package_id');

        if (!$packageId) {
            return Response::json(['error' => 'package_id is required'], 400);
        }

        $tours = $this->packagesService->getCompatibleTours($packageId);

        return Response::json(['tours' => $tours]);
    }

    /**
     * Get available package types
     */
    public function types(): Response
    {
        $types = [
            ['slug' => 'romantic', 'name' => 'Romantic', 'icon' => '💕'],
            ['slug' => 'family', 'name' => 'Family', 'icon' => '👨‍👩‍👧‍👦'],
            ['slug' => 'corporate', 'name' => 'Corporate', 'icon' => '🏢'],
            ['slug' => 'adventure', 'name' => 'Adventure', 'icon' => '🏄'],
            ['slug' => 'party', 'name' => 'Party', 'icon' => '🎉'],
            ['slug' => 'wedding', 'name' => 'Wedding', 'icon' => '💒']
        ];

        return Response::json(['types' => $types]);
    }
}
