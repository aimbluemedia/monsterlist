<?php
// Static-ish pages: /pricing /about /contact /terms /privacy /add-listing

$site = setting('site_name');

switch ($path) {
    case '/pricing':
        $planList = plans();
        // The answers quote plans() and token_rules() rather than repeating
        // numbers by hand, so a price or allowance changed in Settings cannot
        // leave the FAQ promising something the site no longer does. This
        // array also feeds the FAQ structured data below.
        $fqP = plans();
        $fqT = ['free' => token_rules('free'), 'pro' => token_rules('pro'), 'featured' => token_rules('featured')];
        $faq = [
            ['Is the free plan really free?',
             'Yes, and it stays free. One listing with your description, category, city and a link to your website — '
             . 'no credit card, no trial clock. ' . number_format((int)$fqT['free']['grant']) . ' tokens a month come with it.'],
            ['Do I need a credit card to start?',
             'Not for Free. You only enter card details if you choose Pro or Premium, and checkout is handled by Stripe — we never see your card number.'],
            ['How many listings can I have?',
             'One, on every plan. A ' . $site . ' account is one business — what the paid plans change is how much that listing can do, not how many you get.'],
            ['Why upgrade if I still get one listing?',
             'Because a free listing is a line in a directory and a paid one is a page worth landing on. Pro adds your phone number, a public email, your logo, '
             . 'a photo gallery, a video, a ' . number_format(PROFILE_MAX_WORDS) . '-word profile and the analytics to see what it is doing — and '
             . number_format((int)$fqT['pro']['grant']) . ' tokens a month instead of ' . number_format((int)$fqT['free']['grant'])
             . '. Premium puts that page above everyone else\'s in your city and category, and we write and post an article about you every month.'],
            ['How long before my listing is live?',
             'A person reads every listing before it appears — usually the same day. That is the whole point of the directory: nothing goes live unchecked.'],
            ['What is the difference between Pro and Premium?',
             'Pro gives you the full storefront — profile, logo, photos, video, contact details and analytics. Premium adds priority placement across the site, '
             . 'and one article a month that we write for you and post out to our channels and yours.'],
            ['What does priority placement mean?',
             'Premium listings appear on the homepage and at the top of their city and category pages, above Pro and free listings.'],
            ['What are tokens for?',
             'Tokens are how promotions work. You spend ' . (int)$fqT['free']['cost_promo'] . ' to put one of your links in front of the network, and earn them back by opening other members\' links. '
             . 'Free gets ' . number_format((int)$fqT['free']['grant']) . ' a month, Pro ' . number_format((int)$fqT['pro']['grant'])
             . ', Premium ' . number_format((int)$fqT['featured']['grant']) . '.'],
            ['Can I upgrade or switch plans later?',
             'Any time, in either direction. An upgrade takes effect the moment payment goes through, and your listings gain the new features straight away.'],
            ['Can I cancel?',
             'Yes, from your billing page, with no phone call and no notice period. Your plan keeps working until the end of the period you have already paid for.'],
            ['Who writes the article on Premium?',
             'We do — one a month, about your business, posted to our ' . implode(', ', article_channels())
             . ', then promoted out to each of your own channels.'],
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
        if (is_logged_in()) redirect('/members/listings/new');
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
