<?php
// SEO: JSON-LD builders that Google and AI crawlers (GPTBot, ClaudeBot,
// PerplexityBot, Google-Extended) read to understand each page.
// Every public page passes $meta into the layout:
//   ['title', 'description', 'canonical', 'jsonld' => [...], 'og' => [...], 'robots']

/**
 * Schema.org business types a listing can claim, grouped by our own category.
 *
 * Every key is a real type in the LocalBusiness branch of the Schema.org
 * hierarchy. Nothing here is invented: an unrecognised @type is worth less than
 * no @type at all, because a search engine that cannot resolve it falls back to
 * ignoring the block rather than guessing what was meant.
 *
 * This does not replace our sixteen categories — those are what the site
 * browses by, and "Home & Repair" is a better section heading than a list of
 * forty trades. This is the refinement underneath: the category says which
 * shelf a listing sits on, the type says what the business actually is, and
 * only the second one is a thing Google already understands.
 */
function schema_types(): array
{
    return [
        'auto' => [
            'AutoRepair'      => 'Auto repair shop',
            'AutoBodyShop'    => 'Auto body shop',
            'AutoDealer'      => 'Car dealership',
            'AutoPartsStore'  => 'Auto parts store',
            'AutoRental'      => 'Car rental',
            'AutoWash'        => 'Car wash',
            'TireShop'        => 'Tyre shop',
            'MotorcycleRepair'=> 'Motorcycle repair',
            'MotorcycleDealer'=> 'Motorcycle dealer',
            'GasStation'      => 'Petrol / gas station',
        ],
        'beauty' => [
            'HairSalon'                => 'Hair salon',
            'BeautySalon'              => 'Beauty salon',
            'NailSalon'                => 'Nail salon',
            'DaySpa'                   => 'Day spa',
            'TattooParlor'             => 'Tattoo studio',
            'HealthAndBeautyBusiness'  => 'Health & beauty (other)',
        ],
        'creative' => [
            'ProfessionalService' => 'Design / creative studio',
            'ArtGallery'          => 'Art gallery',
        ],
        'education' => [
            'ChildCare' => 'Nursery / childcare',
            'Library'   => 'Library',
        ],
        'events' => [
            'EntertainmentBusiness' => 'Entertainment venue',
            'NightClub'             => 'Night club',
            'ComedyClub'            => 'Comedy club',
            'MovieTheater'          => 'Cinema',
            'AmusementPark'         => 'Amusement park',
            'Casino'                => 'Casino',
        ],
        'food' => [
            'Restaurant'        => 'Restaurant',
            'CafeOrCoffeeShop'  => 'Café / coffee shop',
            'Bakery'            => 'Bakery',
            'BarOrPub'          => 'Bar or pub',
            'FastFoodRestaurant'=> 'Fast food',
            'IceCreamShop'      => 'Ice cream shop',
            'Brewery'           => 'Brewery',
            'Winery'            => 'Winery',
            'Distillery'        => 'Distillery',
            'GroceryStore'      => 'Grocery store',
            'LiquorStore'       => 'Off licence / liquor store',
            'FoodEstablishment' => 'Caterer / other food business',
        ],
        'home' => [
            'Plumber'                    => 'Plumber',
            'Electrician'                => 'Electrician',
            'HVACBusiness'               => 'Heating & air conditioning',
            'RoofingContractor'          => 'Roofer',
            'HousePainter'               => 'Painter & decorator',
            'GeneralContractor'          => 'General contractor / builder',
            'Locksmith'                  => 'Locksmith',
            'MovingCompany'              => 'Removals / moving company',
            'HomeAndConstructionBusiness'=> 'Home & construction (other)',
        ],
        'legal' => [
            'Attorney'          => 'Attorney / solicitor',
            'Notary'            => 'Notary',
            'LegalService'      => 'Legal service (other)',
            'AccountingService' => 'Accountant',
            'InsuranceAgency'   => 'Insurance agency',
            'BankOrCreditUnion' => 'Bank or credit union',
            'FinancialService'  => 'Financial service (other)',
        ],
        'marketing' => [
            'ProfessionalService' => 'Marketing / media agency',
            'RadioStation'        => 'Radio station',
            'TelevisionStation'   => 'Television station',
        ],
        'pets' => [
            'VeterinaryCare' => 'Veterinary practice',
            'PetStore'       => 'Pet shop',
            'AnimalShelter'  => 'Animal shelter',
        ],
        'professional' => [
            'ProfessionalService'  => 'Professional service',
            'EmploymentAgency'     => 'Recruitment agency',
            'TravelAgency'         => 'Travel agency',
            'DryCleaningOrLaundry' => 'Dry cleaner / laundry',
            'SelfStorage'          => 'Self storage',
            'RecyclingCenter'      => 'Recycling centre',
        ],
        'realestate' => [
            'RealEstateAgent' => 'Estate agent / realtor',
        ],
        'retail' => [
            'Store'               => 'Shop (general)',
            'ClothingStore'       => 'Clothing shop',
            'ShoeStore'           => 'Shoe shop',
            'JewelryStore'        => 'Jeweller',
            'FurnitureStore'      => 'Furniture shop',
            'HomeGoodsStore'      => 'Home goods shop',
            'HardwareStore'       => 'Hardware shop',
            'GardenStore'         => 'Garden centre',
            'Florist'             => 'Florist',
            'BookStore'           => 'Book shop',
            'ToyStore'            => 'Toy shop',
            'SportingGoodsStore'  => 'Sports shop',
            'ElectronicsStore'    => 'Electronics shop',
            'MobilePhoneStore'    => 'Mobile phone shop',
            'MusicStore'          => 'Music shop',
            'ConvenienceStore'    => 'Convenience store',
            'DepartmentStore'     => 'Department store',
            'BikeStore'           => 'Bike shop',
            'PawnShop'            => 'Pawn shop',
            'ShoppingCenter'      => 'Shopping centre',
        ],
        'tech' => [
            'ProfessionalService' => 'Software / IT service',
            'ComputerStore'       => 'Computer shop',
            'InternetCafe'        => 'Internet café',
        ],
        'trades' => [
            'GeneralContractor'          => 'General contractor / builder',
            'Electrician'                => 'Electrician',
            'Plumber'                    => 'Plumber',
            'HVACBusiness'               => 'Heating & air conditioning',
            'RoofingContractor'          => 'Roofer',
            'HousePainter'               => 'Painter & decorator',
            'Locksmith'                  => 'Locksmith',
            'HomeAndConstructionBusiness'=> 'Skilled trade (other)',
        ],
        'wellness' => [
            'Dentist'         => 'Dentist',
            'Physician'       => 'Doctor / physician',
            'MedicalClinic'   => 'Medical clinic',
            'Optician'        => 'Optician',
            'Optometric'      => 'Optometrist',
            'Pharmacy'        => 'Pharmacy',
            'Physiotherapy'   => 'Physiotherapist',
            'Psychiatric'     => 'Mental health practice',
            'HealthClub'      => 'Gym / health club',
            'MedicalBusiness' => 'Health practice (other)',
        ],
    ];
}

/** Every valid type, flattened. A type may appear under several categories. */
function schema_type_labels(): array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (schema_types() as $group) {
            foreach ($group as $type => $label) $flat[$type] = $label;
        }
    }
    return $flat;
}

function schema_type_valid(?string $type): bool
{
    return $type !== null && $type !== '' && isset(schema_type_labels()[$type]);
}

/** The human name for a stored type, or '' when there is not one. */
function schema_type_label(?string $type): string
{
    return schema_type_valid($type) ? schema_type_labels()[$type] : '';
}

/**
 * The @type for one listing.
 *
 * LocalBusiness is the honest answer when nobody has said what the business is:
 * it is true of every listing here, where a guess made from the category would
 * be true of some of them.
 */
function business_schema_type(array $b): string
{
    return schema_type_valid($b['business_type'] ?? null) ? (string)$b['business_type'] : 'LocalBusiness';
}

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
        '@type'      => business_schema_type($b),
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
