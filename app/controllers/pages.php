<?php
// Static-ish pages: /pricing /about /contact /terms /privacy /add-listing

$site = setting('site_name');

switch ($path) {
    case '/pricing':
        $planList = plans();
        $faq = [
            ['Is the free plan really free?', 'Yes. One basic listing, forever free — name, category, location and contact details, visible in search and city pages.'],
            ['Can I cancel my monthly membership anytime?', 'Yes. Manage or cancel from your billing page; your plan stays active until the end of the paid period.'],
            ['What does Featured placement mean?', 'Featured listings appear on the homepage and at the top of their city and category pages, above Pro and free listings.'],
            ['How do upgrades work?', 'Checkout is handled securely by Stripe. Your listings upgrade instantly after payment.'],
        ];
        $meta = [
            'title'       => "Pricing — free listings & monthly memberships | $site",
            'description' => 'List your business free, or upgrade to Pro or Featured for a full storefront, priority placement and analytics. Simple monthly pricing, cancel anytime.',
            'canonical'   => site_url('/pricing'),
            'jsonld'      => [jsonld_faq($faq), jsonld_breadcrumbs([
                ['name' => 'Home', 'path' => '/'], ['name' => 'Pricing', 'path' => '/pricing'],
            ])],
        ];
        view('pages/pricing', compact('meta', 'planList', 'faq'));
        break;

    case '/add-listing':
        // Marketing page that routes to signup (or straight to the form if logged in)
        if (is_logged_in()) redirect('/account/listings/new');
        redirect('/signup');

    case '/about':
        $meta = [
            'title'       => "About — $site",
            'description' => "$site is a worldwide small-business directory built to help local businesses get found — in search engines and AI assistants alike.",
            'canonical'   => site_url('/about'),
        ];
        view('pages/about', compact('meta'));
        break;

    case '/contact':
        $sent = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $from = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
            $msg  = mb_substr(post('message'), 0, 4000);
            if ($from && $msg !== '') {
                send_mail($GLOBALS['config']['mail']['from'], "$site contact form", "From: $from\n\n$msg");
                $sent = true;
            }
        }
        $meta = [
            'title'       => "Contact — $site",
            'description' => "Questions about listings or memberships? Get in touch with the $site team.",
            'canonical'   => site_url('/contact'),
        ];
        view('pages/contact', compact('meta', 'sent'));
        break;

    case '/terms':
        $meta = [
            'title'       => "Terms of service — $site",
            'description' => "The terms that govern use of $site listings and memberships.",
            'canonical'   => site_url('/terms'),
        ];
        view('pages/terms', compact('meta'));
        break;

    case '/privacy':
        $meta = [
            'title'       => "Privacy policy — $site",
            'description' => "How $site collects, uses and protects your data.",
            'canonical'   => site_url('/privacy'),
        ];
        view('pages/privacy', compact('meta'));
        break;

    default:
        not_found();
}
