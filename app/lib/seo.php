<?php
// SEO: JSON-LD builders that Google and AI crawlers (GPTBot, ClaudeBot,
// PerplexityBot, Google-Extended) read to understand each page.
// Every public page passes $meta into the layout:
//   ['title', 'description', 'canonical', 'jsonld' => [...], 'og' => [...], 'robots']

function jsonld_website(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => setting('site_name'),
        'url'      => site_url('/'),
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => site_url('/search?q={search_term_string}')],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function jsonld_organization(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => setting('site_name'),
        'url'      => site_url('/'),
        'logo'     => site_url('/assets/img/logo.png'),
    ];
}

function jsonld_breadcrumbs(array $items): array
{
    $els = [];
    foreach ($items as $i => $it) {
        $els[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $it['name'],
            'item'     => site_url($it['path']),
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $els];
}

function jsonld_itemlist(array $entries): array
{
    $els = [];
    foreach ($entries as $i => $e) {
        $els[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $e['name'],
            'url'      => isset($e['path']) ? site_url($e['path']) : null,
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $els];
}

function jsonld_local_business(array $b, array $city, string $path): array
{
    // Contact details are a paid feature: the storefront prints the phone and
    // email only for an enhanced tier. Schema has to obey the same rule, and
    // did not — a free listing that still holds a phone from a lapsed
    // subscription was publishing it in machine-readable form on a page that
    // shows nothing of the sort. That is the paid feature given away to every
    // crawler, and Google's structured-data guidelines require marked-up
    // content to be present on the page it describes.
    $enhanced = tier_enhanced($b['tier'] ?? null);

    $data = [
        '@context'   => 'https://schema.org',
        '@type'      => 'LocalBusiness',
        '@id'        => site_url($path),
        'name'       => $b['name'],
        'description'=> meta_excerpt((string)$b['description'], 200) ?: $b['tagline'],
        'url'        => $b['website'] ?: site_url($path),
        'telephone'  => $enhanced ? $b['phone'] : null,
        'email'      => $enhanced ? ($b['email'] ?? null) : null,
        // priceRange was hard-coded to "$$" for all 695 listings. Nobody has
        // ever been asked what a listing costs, so that was an invented fact
        // about somebody else's business, published under their name. Omitted
        // until it is a field somebody fills in.
        'address'    => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $b['address'],
            'addressLocality' => $city['name'],
            'addressRegion'   => $city['region_code'] ?? $city['region_name'] ?? null,
            'addressCountry'  => $city['country_code'] ?? $city['ccode'],
        ],
    ];
    if (!empty($b['lat']) && !empty($b['lng'])) {
        $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)$b['lat'], 'longitude' => (float)$b['lng']];
    }
    if ((float)$b['rating'] > 0 && (int)$b['review_count'] > 0) {
        $data['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => (float)$b['rating'],
            'reviewCount' => (int)$b['review_count'],
        ];
    }
    return array_filter($data, fn($v) => $v !== null && $v !== '');
}

function jsonld_faq(array $qas): array
{
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(fn($qa) => [
            '@type'          => 'Question',
            'name'           => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
        ], $qas),
    ];
}

/** Print all <script type="application/ld+json"> blocks. */
function render_jsonld(array $blocks): void
{
    foreach ($blocks as $b) {
        echo '<script type="application/ld+json">'
           . json_encode($b, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
           . '</script>' . "\n";
    }
}
