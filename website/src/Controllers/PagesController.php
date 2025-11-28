<?php

declare(strict_types=1);

namespace Website\Controllers;

/**
 * Static pages controller
 */
class PagesController extends BaseController
{
    /**
     * About page
     */
    public function about(array $params): void
    {
        $this->seo
            ->title('About Us')
            ->description('Learn about Phuket Yacht & Tours - your premier yacht charter and island tour company in Phuket, Thailand.')
            ->canonical('/about')
            ->breadcrumbs([
                'Home' => '/',
                'About Us' => '/about',
            ]);

        // Get team members, stats, etc.
        $stats = $this->cache('about_stats', 3600, function () {
            return [
                'vessels' => $this->db->queryOne("SELECT COUNT(*) as count FROM vessels WHERE is_active = 1")['count'],
                'tours' => $this->db->queryOne("SELECT COUNT(*) as count FROM tours WHERE is_active = 1")['count'],
                'bookings' => $this->db->queryOne("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")['count'],
                'reviews' => $this->db->queryOne("SELECT AVG(rating) as avg FROM reviews WHERE status = 'approved'")['avg'],
            ];
        });

        $this->render('pages/about', [
            'stats' => $stats,
        ]);
    }

    /**
     * Contact page
     */
    public function contact(array $params): void
    {
        $this->seo
            ->title('Contact Us')
            ->description('Get in touch with Phuket Yacht & Tours. We are here to help you plan your perfect yacht charter or island tour.')
            ->canonical('/contact')
            ->breadcrumbs([
                'Home' => '/',
                'Contact' => '/contact',
            ]);

        $this->render('pages/contact', [
            'csrfToken' => $this->csrfToken(),
            'messageSent' => isset($_SESSION['contact_sent']),
        ]);

        unset($_SESSION['contact_sent']);
    }

    /**
     * Send contact form
     */
    public function sendContact(array $params): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/contact');
            return;
        }

        $name = $this->post('name', '');
        $email = $this->post('email', '');
        $phone = $this->post('phone', '');
        $message = $this->post('message', '');

        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            $this->redirect('/contact?error=1');
            return;
        }

        // Save to database
        $this->db->insert('contact_messages', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // TODO: Send email notification

        $_SESSION['contact_sent'] = true;
        $this->redirect('/contact');
    }

    /**
     * FAQ page
     */
    public function faq(array $params): void
    {
        $this->seo
            ->title('Frequently Asked Questions')
            ->description('Find answers to common questions about yacht rentals, island tours, booking, and payment in Phuket.')
            ->canonical('/faq')
            ->breadcrumbs([
                'Home' => '/',
                'FAQ' => '/faq',
            ]);

        $faqs = [
            [
                'q' => 'How do I book a yacht or tour?',
                'a' => 'You can book directly through our website or via our Telegram bot. Simply select your preferred yacht or tour, choose your date and time, and complete the booking form.',
            ],
            [
                'q' => 'What payment methods do you accept?',
                'a' => 'We accept Telegram Stars, bank transfers, and major credit cards. Payment can be made online or at our office.',
            ],
            [
                'q' => 'Is the captain included in yacht rental?',
                'a' => 'Yes, all our yacht rentals include an experienced captain. You can relax and enjoy your trip while our professional crew takes care of navigation.',
            ],
            [
                'q' => 'What is your cancellation policy?',
                'a' => 'Free cancellation up to 48 hours before your booking. Cancellations within 48 hours may be subject to a 50% fee.',
            ],
            [
                'q' => 'Do you provide hotel pickup?',
                'a' => 'Yes, hotel pickup and drop-off is available for most tours. Yacht charters depart from Chalong Bay pier.',
            ],
            [
                'q' => 'What should I bring?',
                'a' => 'We recommend bringing sunscreen, sunglasses, a hat, swimwear, a towel, and a camera. Everything else is provided.',
            ],
            [
                'q' => 'Are children allowed?',
                'a' => 'Yes, children are welcome on most of our yachts and tours. We have special child prices for tours.',
            ],
            [
                'q' => 'What happens if the weather is bad?',
                'a' => 'Safety is our priority. If weather conditions are unsafe, we will offer to reschedule your booking or provide a full refund.',
            ],
        ];

        $this->render('pages/faq', [
            'faqs' => $faqs,
        ]);
    }

    /**
     * Terms page
     */
    public function terms(array $params): void
    {
        $this->seo
            ->title('Terms of Service')
            ->description('Read our terms and conditions for yacht rentals and island tours in Phuket.')
            ->canonical('/terms');

        $this->render('pages/terms');
    }

    /**
     * Privacy page
     */
    public function privacy(array $params): void
    {
        $this->seo
            ->title('Privacy Policy')
            ->description('Learn how we protect your personal information at Phuket Yacht & Tours.')
            ->canonical('/privacy');

        $this->render('pages/privacy');
    }

    /**
     * Set language
     */
    public function setLanguage(array $params): void
    {
        $code = $params['code'] ?? 'en';

        if (in_array($code, ['en', 'ru', 'th'])) {
            $_SESSION['lang'] = $code;
        }

        // Redirect back
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $url = parse_url($referer, PHP_URL_PATH) ?? '/';

        // Update lang param in query string
        $query = parse_url($referer, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        $params['lang'] = $code;

        $this->redirect($url . '?' . http_build_query($params));
    }
}
