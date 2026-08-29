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
 * Every Schema.org type a subcategory may claim, as type => label.
 *
 * This is a closed vocabulary, and that is the whole point of it being a list
 * rather than a text box. @type is not a label: a search engine resolves it
 * against schema.org and, finding nothing, discards the block rather than
 * guessing. So a made-up name does not give you a worse rich result, it gives
 * you none — silently, with the page still looking perfectly fine.
 *
 * Which is also why nothing here is derived from a category name by machine.
 * "Real Estate Broker" would CamelCase into RealEstateBroker, which looks
 * exactly right and is not a type schema.org defines. Plausible-and-wrong is
 * worse than absent, because nobody goes back to check it.
 *
 * Everything below is a real type in the LocalBusiness branch (plus the school
 * types, which are Organization but are what a school should publish). Where a
 * trade has no type of its own, the honest answer is no type at all: the
 * listing stays a LocalBusiness, which is true of every listing here.
 */
function schema_type_catalog(): array
{
    static $cat = null;
    if ($cat !== null) return $cat;

    $cat = [
        // Motor trade
        'AutomotiveBusiness' => 'Automotive business (general)',
        'AutoBodyShop' => 'Auto body shop', 'AutoDealer' => 'Car dealership',
        'AutoPartsStore' => 'Auto parts store', 'AutoRental' => 'Car rental',
        'AutoRepair' => 'Auto repair shop', 'AutoWash' => 'Car wash',
        'GasStation' => 'Petrol / gas station', 'MotorcycleDealer' => 'Motorcycle dealer',
        'MotorcycleRepair' => 'Motorcycle repair', 'TireShop' => 'Tyre shop',

        // Health & beauty
        'HealthAndBeautyBusiness' => 'Health & beauty (general)',
        'BeautySalon' => 'Beauty salon', 'DaySpa' => 'Day spa', 'HairSalon' => 'Hair salon',
        'NailSalon' => 'Nail salon', 'TattooParlor' => 'Tattoo studio',

        // Schools
        'Preschool' => 'Preschool / nursery', 'School' => 'School',
        'ElementarySchool' => 'Primary / elementary school', 'MiddleSchool' => 'Middle school',
        'HighSchool' => 'Secondary / high school', 'CollegeOrUniversity' => 'College or university',
        'ChildCare' => 'Childcare / nursery', 'Library' => 'Library',

        // Emergency & public
        'EmergencyService' => 'Emergency service', 'FireStation' => 'Fire station',
        'PoliceStation' => 'Police station', 'Hospital' => 'Hospital',
        'GovernmentOffice' => 'Government office', 'PostOffice' => 'Post office',
        'RecyclingCenter' => 'Recycling centre', 'AnimalShelter' => 'Animal shelter',
        'ArchiveOrganization' => 'Archive',

        // Going out
        'EntertainmentBusiness' => 'Entertainment (general)',
        'AdultEntertainment' => 'Adult entertainment', 'AmusementPark' => 'Amusement park',
        'ArtGallery' => 'Art gallery', 'Casino' => 'Casino', 'ComedyClub' => 'Comedy club',
        'MovieTheater' => 'Cinema / movie theater', 'NightClub' => 'Nightclub',

        // Money
        'FinancialService' => 'Financial service (general)',
        'AccountingService' => 'Accountant', 'AutomatedTeller' => 'Cash machine / ATM',
        'BankOrCreditUnion' => 'Bank or credit union', 'InsuranceAgency' => 'Insurance agency',

        // Food & drink
        'FoodEstablishment' => 'Food business (general)', 'Bakery' => 'Bakery',
        'BarOrPub' => 'Bar or pub', 'Brewery' => 'Brewery',
        'CafeOrCoffeeShop' => 'Cafe / coffee shop', 'Distillery' => 'Distillery',
        'FastFoodRestaurant' => 'Fast food', 'IceCreamShop' => 'Ice cream shop',
        'Restaurant' => 'Restaurant', 'Winery' => 'Winery',

        // Trades
        'HomeAndConstructionBusiness' => 'Home & construction (general)',
        'Electrician' => 'Electrician', 'GeneralContractor' => 'General contractor',
        'HVACBusiness' => 'Heating & air conditioning', 'HousePainter' => 'Painter & decorator',
        'Locksmith' => 'Locksmith', 'MovingCompany' => 'Removals / moving company',
        'Plumber' => 'Plumber', 'RoofingContractor' => 'Roofer',

        // Law
        'LegalService' => 'Legal service (general)', 'Attorney' => 'Solicitor / attorney',
        'Notary' => 'Notary',

        // Staying
        'LodgingBusiness' => 'Accommodation (general)', 'BedAndBreakfast' => 'B&B',
        'Campground' => 'Campsite', 'Hostel' => 'Hostel', 'Hotel' => 'Hotel',
        'Motel' => 'Motel', 'Resort' => 'Resort',

        // Health
        'MedicalBusiness' => 'Health practice (general)', 'Dentist' => 'Dentist',
        'CommunityHealth' => 'Community health', 'Dermatology' => 'Dermatology',
        'DietNutrition' => 'Dietitian / nutrition', 'Geriatric' => 'Geriatrics',
        'Gynecologic' => 'Gynaecology', 'MedicalClinic' => 'Medical clinic',
        'Midwifery' => 'Midwifery', 'Nursing' => 'Nursing', 'Obstetric' => 'Obstetrics',
        'Oncologic' => 'Oncology', 'Optician' => 'Optician', 'Optometric' => 'Optometrist',
        'Otolaryngologic' => 'ENT', 'Pediatric' => 'Paediatrics', 'Pharmacy' => 'Pharmacy',
        'Physician' => 'Doctor / physician', 'Physiotherapy' => 'Physiotherapy',
        'PlasticSurgery' => 'Plastic surgery', 'Podiatric' => 'Podiatry / chiropody',
        'PrimaryCare' => 'GP / primary care', 'Psychiatric' => 'Mental health practice',
        'PublicHealth' => 'Public health', 'VeterinaryCare' => 'Vet',

        // Services
        'ProfessionalService' => 'Professional service (general)',
        'EmploymentAgency' => 'Recruitment agency', 'TravelAgency' => 'Travel agency',
        'DryCleaningOrLaundry' => 'Dry cleaner / laundry', 'SelfStorage' => 'Self storage',
        'InternetCafe' => 'Internet cafe', 'RealEstateAgent' => 'Estate agent / realtor',
        'TouristInformationCenter' => 'Tourist information',
        'RadioStation' => 'Radio station', 'TelevisionStation' => 'Television station',

        // Sport & fitness
        'SportsActivityLocation' => 'Sports venue (general)', 'BowlingAlley' => 'Bowling alley',
        'ExerciseGym' => 'Gym', 'GolfCourse' => 'Golf course', 'HealthClub' => 'Health club',
        'PublicSwimmingPool' => 'Swimming pool', 'SkiResort' => 'Ski resort',
        'SportsClub' => 'Sports club', 'StadiumOrArena' => 'Stadium or arena',
        'TennisComplex' => 'Tennis centre',

        // Shops
        'Store' => 'Shop (general)', 'BikeStore' => 'Bike shop', 'BookStore' => 'Bookshop',
        'ClothingStore' => 'Clothes shop', 'ComputerStore' => 'Computer shop',
        'ConvenienceStore' => 'Convenience store', 'DepartmentStore' => 'Department store',
        'ElectronicsStore' => 'Electronics shop', 'Florist' => 'Florist',
        'FurnitureStore' => 'Furniture shop', 'GardenStore' => 'Garden centre',
        'GroceryStore' => 'Grocery shop', 'HardwareStore' => 'Hardware / DIY shop',
        'HobbyShop' => 'Hobby shop', 'HomeGoodsStore' => 'Homeware shop',
        'JewelryStore' => 'Jeweller', 'LiquorStore' => 'Off licence / liquor store',
        'MensClothingStore' => "Men's clothing", 'MobilePhoneStore' => 'Mobile phone shop',
        'MovieRentalStore' => 'Video rental', 'MusicStore' => 'Music shop',
        'OfficeEquipmentStore' => 'Office supplies', 'OutletStore' => 'Outlet shop',
        'PawnShop' => 'Pawnbroker', 'PetStore' => 'Pet shop', 'ShoeStore' => 'Shoe shop',
        'ShoppingCenter' => 'Shopping centre', 'SportingGoodsStore' => 'Sports shop',
        'ToyStore' => 'Toy shop', 'WholesaleStore' => 'Wholesaler',
    ];
    asort($cat, SORT_NATURAL | SORT_FLAG_CASE);
    return $cat;
}

/**
 * The type a subcategory name points at, or '' when nothing does.
 *
 * Matches the name against both the type names and their labels, ignoring case,
 * spaces and punctuation — so "Hair salon", "hair-salon" and "HairSalon" all
 * land on HairSalon. It only ever returns something already in the catalogue,
 * which is what stops it inventing one.
 *
 * A name that describes rather than names a trade — "Plumbing (emergency)",
 * "Marketing / media agency" — matches nothing, and that is the right answer:
 * those are the ones a person should look at.
 */
function schema_type_guess(string $name): string
{
    static $index = null;
    if ($index === null) {
        $index = [];
        foreach (schema_type_catalog() as $type => $label) {
            // Types first, then labels, and neither overwrites an earlier key —
            // so an exact type name always wins over somebody else's label.
            $index[schema_name_key($type)]  = $index[schema_name_key($type)] ?? $type;
            $index[schema_name_key($label)] = $index[schema_name_key($label)] ?? $type;
        }
    }
    $key = schema_name_key($name);
    return $key === '' ? '' : ($index[$key] ?? '');
}

/** Down to letters and digits, so spelling variations meet in the middle. */
function schema_name_key(string $s): string
{
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
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

/** Is the category-level type column there? */
function category_schema_ready(): bool
{
    static $ok = null;
    if ($ok === null) $ok = column_exists('categories', 'schema_type');
    return $ok;
}

/**
 * What a category says its listings are, when nothing more specific is known.
 *
 * The general types — AutomotiveBusiness, FoodEstablishment, Store — exist for
 * exactly this. They are not as good as Plumber, but they are a great deal
 * better than LocalBusiness, and they are true of everything on the shelf.
 */
function category_schema_type(?string $categoryId): string
{
    if ($categoryId === null || $categoryId === '' || !category_schema_ready()) return '';
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (rows('SELECT id, schema_type FROM categories') as $c) $map[$c['id']] = (string)$c['schema_type'];
    }
    $t = $map[$categoryId] ?? '';
    return schema_type_valid($t) ? $t : '';
}

/**
 * Sensible starting types for the sixteen categories we ship.
 *
 * Two are deliberately blank. "Education & Tutoring" covers a nursery and a
 * maths tutor; "Pets & Animals" covers a vet, a groomer and a pet shop. There
 * is no Schema.org word that means all of those, and picking one would say
 * something false about the rest — which is worse than saying only that they
 * are local businesses, because a wrong type is a wrong claim.
 */
function category_schema_defaults(): array
{
    return [
        'auto'         => 'AutomotiveBusiness',
        'beauty'       => 'HealthAndBeautyBusiness',
        'creative'     => 'ProfessionalService',
        'education'    => '',
        'events'       => 'EntertainmentBusiness',
        'food'         => 'FoodEstablishment',
        'home'         => 'HomeAndConstructionBusiness',
        'legal'        => 'LegalService',
        'marketing'    => 'ProfessionalService',
        'pets'         => '',
        'professional' => 'ProfessionalService',
        'realestate'   => 'RealEstateAgent',
        'retail'       => 'Store',
        'tech'         => 'ProfessionalService',
        'trades'       => 'HomeAndConstructionBusiness',
        'wellness'     => 'MedicalBusiness',
    ];
}

/**
 * Fill in category types that have never been set. Returns how many it set.
 *
 * Only ever writes over an empty value, so a choice made on the Categories page
 * — including a deliberate "None" — is never undone by this running again.
 */
function category_schema_seed(): int
{
    if (!category_schema_ready()) return 0;
    $n = 0;
    foreach (category_schema_defaults() as $id => $type) {
        if ($type === '') continue;
        $n += q('UPDATE categories SET schema_type = ? WHERE id = ? AND schema_type = ""', [$type, $id])->rowCount();
    }
    return $n;
}

/**
 * The @type for one listing, most specific thing known about it.
 *
 * Three steps down: the subcategory the listing chose, then the type its
 * category carries, then LocalBusiness. The middle step is the point — a
 * listing filed under Real Estate is a RealEstateAgent whether or not anybody
 * ever picked a trade for it, and publishing LocalBusiness instead threw that
 * away for no reason.
 *
 * Every step is checked against the catalogue before it is used, so no route
 * through here can put a name into @type that Schema.org does not define.
 */
function business_schema_type(array $b): string
{
    if (schema_type_valid($b['business_type'] ?? null)) return (string)$b['business_type'];
    $fromCategory = category_schema_type($b['category_id'] ?? null);
    return $fromCategory !== '' ? $fromCategory : 'LocalBusiness';
}

/**
 * May a listing store this key?
 *
 * Wider than schema_type_valid(), and it has to be. That one asks "is this a
 * real Schema.org type", which is the right question for the markup and the
 * wrong one for the form: a subcategory with no Schema.org type is offered in
 * the dropdown under an x- key, and validating the save against the catalogue
 * threw it away silently. The listing kept no subcategory at all and nothing
 * said so.
 *
 * So the form validates against what the form offered — the live list — and
 * business_schema_type() still decides separately what may be published, which
 * is where an x- key turns into the category's type or LocalBusiness.
 */
function listing_type_selectable(?string $key): bool
{
    if ($key === null || $key === '') return false;
    if (schema_type_valid($key)) return true;
    foreach (schema_types() as $group) {
        if (isset($group[$key])) return true;
    }
    return false;
}

/**
 * The label for whatever a listing stored, live list first.
 *
 * Covers the x- keys too, which schema_type_label() cannot: those are not in
 * the catalogue, so it would return '' and a page would show a listing with a
 * subcategory as having none.
 */
function listing_type_label(?string $key): string
{
    if ($key === null || $key === '') return '';
    foreach (schema_types() as $group) {
        if (isset($group[$key])) return $group[$key];
    }
    return schema_type_label($key);
}

/**
 * The type a subcategory publishes, and where it came from.
 *
 * Returns [type, source] where source is 'own', 'category' or ''. The admin
 * page shows this so a blank type reads as "inherits RealEstateAgent" rather
 * than "none", which was the thing that looked broken.
 */
function subcategory_effective_type(array $t): array
{
    if (schema_type_valid($t['schema_type'] ?? null)) return [(string)$t['schema_type'], 'own'];
    $inherited = category_schema_type($t['category_id'] ?? null);
    return $inherited !== '' ? [$inherited, 'category'] : ['', ''];
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
function category_type_save(int $id, string $categoryId, string $schemaType, string $label, int $sort,
                            ?string &$note = null): array
{
    $note = null;
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

    $was = $id ? row('SELECT * FROM category_types WHERE id = ?', [$id]) : null;

    // The name picks the type, unless a person has picked one themselves.
    //
    // Two cases get it: a new subcategory with nothing chosen, and a rename
    // whose old type was the one the old name would have given anyway — that
    // one was the machine's guess, so the machine may revise it. A type that
    // disagreed with its name was somebody's decision, and renaming is not the
    // moment to overrule it.
    //
    // Changing the dropdown in the same save always wins: that is a person
    // saying which one they want, in the clearest way the form allows.
    $typeTouched = $was && $schemaType !== (string)$was['schema_type'];
    $renamed     = $was && $label !== (string)$was['label'];
    $wasGuess    = $was && (string)$was['schema_type'] === schema_type_guess((string)$was['label']);

    if (!$typeTouched && ($was === null ? $schemaType === '' : ($renamed && $wasGuess))) {
        $guess = schema_type_guess($label);
        if ($guess !== '' && $guess !== $schemaType) {
            $note = 'Matched "' . $label . '" to ' . $guess . ' (' . schema_type_catalog()[$guess] . ').';
            $schemaType = $guess;
        }
    }

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
