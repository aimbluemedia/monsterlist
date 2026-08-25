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
function schema_types_builtin(): array
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

/** Is the editable subcategory table there? Same rule as tokens and intake. */
function category_types_ready(): bool
{
    static $ok = null;
    if ($ok === null) $ok = table_exists('category_types');
    return $ok;
}

/**
 * The live list: whatever is in category_types, or the built-in one.
 *
 * The fallback is the point of keeping schema_types_builtin() around. A site
 * that has not run database/upgrade-all.sql yet has no table to read, and the
 * listing forms still need their type list — so it degrades to the hundred and
 * eight that used to be compiled in, exactly as before, rather than offering an
 * empty dropdown.
 *
 * An empty table is treated the same as a missing one. That is deliberate: it
 * makes "delete the lot and start again" a thing you can do without the forms
 * going blank, and the Categories page offers to re-seed from here.
 */
function schema_types(): array
{
    static $live = null;
    if ($live !== null) return $live;

    if (!category_types_ready()) return $live = schema_types_builtin();

    $out = [];
    foreach (rows('SELECT category_id, schema_type, label FROM category_types ORDER BY category_id, sort, label') as $r) {
        // The key is the Schema.org type, which is what a listing stores. Rows
        // with no type are keyed by label instead, so a subcategory that maps
        // to nothing Schema.org knows about is still selectable — it just ends
        // up as a plain LocalBusiness in the markup, which is honest.
        $key = $r['schema_type'] !== '' ? $r['schema_type'] : 'x-' . slugify($r['label']);
        $out[$r['category_id']][$key] = $r['label'];
    }
    return $live = ($out ?: schema_types_builtin());
}

/**
 * Types the picker may offer, as type => label.
 *
 * The catalogue is the built-in list, because every name in it is a real
 * Schema.org type that has been checked. Inventing one here would put a name
 * into the JSON-LD that Schema.org does not define, and a search engine that
 * cannot resolve an @type discards the block rather than guessing — so a
 * subcategory with no matching type is better off with none at all.
 */
function schema_type_catalog(): array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (schema_types_builtin() as $group) {
            foreach ($group as $type => $label) $flat[$type] = $label;
        }
        asort($flat, SORT_NATURAL | SORT_FLAG_CASE);
    }
    return $flat;
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

/**
 * Is this a type we are willing to publish?
 *
 * Checked against the catalogue of real Schema.org names, not against whatever
 * is in the table. A subcategory keyed "x-something" is a real choice on the
 * form and a real thing to browse by, but it is not a Schema.org type, so it
 * must not reach the markup — business_schema_type() turns it into
 * LocalBusiness instead.
 */
function schema_type_valid(?string $type): bool
{
    return $type !== null && $type !== '' && isset(schema_type_catalog()[$type]);
}

/**
 * The human name for a stored type, or '' when there is not one.
 *
 * Reads the live list first, so renaming a subcategory renames it everywhere it
 * is shown; falls back to the catalogue for a type no longer listed, which
 * still deserves its proper name on the listings that already have it.
 */
function schema_type_label(?string $type): string
{
    if ($type === null || $type === '') return '';
    return schema_type_labels()[$type] ?? (schema_type_catalog()[$type] ?? '');
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
            // Paid, so gated with the rest of them.
            'postalCode'      => $enhanced ? ($b['postcode'] ?? null) : null,
            'addressCountry'  => $city['country_code'] ?? $city['ccode'],
        ],
    ];
    $data['address'] = array_filter($data['address'], fn($v) => $v !== null && $v !== '');

    // openingHoursSpecification, one entry per day the business is open. Closed
    // days are left out: Schema.org describes when somewhere IS open, and a
    // day with no entry is already understood as not open.
    if ($enhanced) {
        $spec = [];
        foreach (hours_parse($b['hours'] ?? null) as $day) {
            if (empty($day['open'])) continue;
            $spec[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $day['d'],
                'opens'     => $day['from'],
                'closes'    => $day['to'],
            ];
        }
        if ($spec) $data['openingHoursSpecification'] = $spec;
    }
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

// ---------------------------------------------------------------------------
// Editing the subcategories. Everything below is the staff Categories page
// talking to category_types; nothing public reads it.
// ---------------------------------------------------------------------------

/** Every subcategory with its category and how many listings use it. */
function category_type_rows(?string $categoryId = null): array
{
    if (!category_types_ready()) return [];
    $sql = 'SELECT t.*, (SELECT COUNT(*) FROM businesses b WHERE b.business_type = t.schema_type
                          AND t.schema_type <> "") AS in_use
              FROM category_types t';
    $args = [];
    if ($categoryId !== null) { $sql .= ' WHERE t.category_id = ?'; $args[] = $categoryId; }
    return rows($sql . ' ORDER BY t.category_id, t.sort, t.label', $args);
}

/** The same, grouped by category id, for a page that draws them in nests. */
function category_types_grouped(): array
{
    $out = [];
    foreach (category_type_rows() as $r) $out[$r['category_id']][] = $r;
    return $out;
}

/**
 * Plant the built-in list, once.
 *
 * Runs when the table exists and is empty, which is the state a site is in the
 * moment the upgrade SQL has been run and before anybody has touched this page.
 * INSERT IGNORE rather than a count check inside the loop: the unique key on
 * (category_id, label) already says what may not happen twice, so a second run
 * changes nothing.
 *
 * Skips a category the site does not have. The built-in list names our sixteen;
 * a site that deleted one should not have it silently rebuilt as a dangling
 * subcategory pointing at a category that is not there.
 */
function category_types_seed(): int
{
    if (!category_types_ready()) return 0;

    $have = [];
    foreach (rows('SELECT id FROM categories') as $c) $have[$c['id']] = true;

    $n = 0;
    foreach (schema_types_builtin() as $catId => $types) {
        if (!isset($have[$catId])) continue;
        $sort = 0;
        foreach ($types as $type => $label) {
            q('INSERT IGNORE INTO category_types (category_id, schema_type, label, sort) VALUES (?,?,?,?)',
              [$catId, $type, $label, $sort += 10]);
            $n += db()->lastInsertId() ? 1 : 0;
        }
    }
    return $n;
}

/**
 * Add or change one subcategory. Returns [id, error].
 *
 * The Schema.org type is checked against the catalogue rather than trusted,
 * because this is the field that ends up in the JSON-LD. Empty is allowed and
 * means "no type" — the listing stays a plain LocalBusiness, which is true of
 * every listing here and therefore never wrong.
 */
function category_type_save(int $id, string $categoryId, string $schemaType, string $label, int $sort): array
{
    if (!category_types_ready()) return [0, 'Subcategories are not installed — run database/upgrade-all.sql.'];

    $label = trim(preg_replace('/\s+/u', ' ', $label));
    if ($label === '') return [0, 'Give the subcategory a name.'];
    $label = mb_substr($label, 0, 120);

    if (!category_by_id($categoryId)) return [0, 'No such category.'];
    if ($schemaType !== '' && !schema_type_valid($schemaType)) {
        return [0, '"' . $schemaType . '" is not a Schema.org business type. Leave it blank if none fits.'];
    }

    $clash = row('SELECT id FROM category_types WHERE category_id = ? AND label = ? AND id <> ?',
                 [$categoryId, $label, $id]);
    if ($clash) return [0, '"' . $label . '" is already under that category.'];

    if ($id) {
        q('UPDATE category_types SET category_id=?, schema_type=?, label=?, sort=? WHERE id=?',
          [$categoryId, $schemaType, $label, $sort, $id]);
        return [$id, ''];
    }
    q('INSERT INTO category_types (category_id, schema_type, label, sort) VALUES (?,?,?,?)',
      [$categoryId, $schemaType, $label, $sort]);
    return [(int)db()->lastInsertId(), ''];
}

/**
 * Remove a subcategory. Refused while listings still claim its type.
 *
 * Deleting it would not clear businesses.business_type, so those listings would
 * keep publishing a type with nothing on this page explaining it. Say the
 * number instead and let staff decide.
 */
function category_type_delete(int $id): array
{
    if (!category_types_ready()) return [false, 'Subcategories are not installed.'];
    $t = row('SELECT * FROM category_types WHERE id = ?', [$id]);
    if (!$t) return [false, 'No such subcategory.'];

    if ($t['schema_type'] !== '') {
        $inUse = (int)scalar('SELECT COUNT(*) FROM businesses WHERE business_type = ?', [$t['schema_type']]);
        // Only blocks when this is the last row offering that type: the same
        // Schema.org type can sit under two categories, and removing one of
        // them leaves the listings a home.
        $others = (int)scalar('SELECT COUNT(*) FROM category_types WHERE schema_type = ? AND id <> ?',
                              [$t['schema_type'], $id]);
        if ($inUse > 0 && $others === 0) {
            return [false, 'Cannot delete — ' . $inUse . ' listing' . ($inUse === 1 ? '' : 's')
                         . ' still set to "' . $t['label'] . '". Change those first.'];
        }
    }
    q('DELETE FROM category_types WHERE id = ?', [$id]);
    return [true, ''];
}
