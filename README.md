# MonsterList

A worldwide small-business directory — **PHP 8 + MySQL**, SEO-first, built to be
indexed by Google **and** AI crawlers (GPTBot, ClaudeBot, PerplexityBot,
Google-Extended). Runs on any shared host (cPanel) — no Composer, no framework,
no build step.

## Features

**Public site**
- Homepage: hero search, top categories, featured members, new members, top locations
- Geo drill-down with the SEO URL backbone:
  `/us` → `/us/arizona` → `/us/arizona/phoenix` → `/us/arizona/phoenix/sunrise-bakery`
  (non-US countries are 2-tier: `/gb/london/business-name`)
- Category landing pages (`/category/food`), full-text search, business storefronts
- **Category × location SEO pages** (`/category/food/us/arizona/phoenix`) with unique
  intro copy, rendered FAQs + `FAQPage` JSON-LD, and internal links (nearby cities,
  other categories) — pages with zero listings are noindexed automatically
- JSON-LD everywhere: `WebSite`+`SearchAction`, `Organization`, `LocalBusiness`,
  `BreadcrumbList`, `ItemList`, `FAQPage`
- Dynamic paginated sitemaps (`/sitemap.xml` index → static/geo/business/category×city
  parts, 10k URLs each)
- `robots.txt` that explicitly welcomes AI crawlers, plus `/llms.txt` (machine-readable
  site guide), a "Quick facts" block on every storefront, and **IndexNow** pings when
  listings go live
- Reviews with cached aggregate ratings
- Logo + photo gallery **uploads** (GD resize server-side, execution-proof uploads dir)
- **Claim-listing flow**: unclaimed businesses can be claimed by members; admins verify
  and approve from a claims queue; claimant is emailed either way
- Transactional email: welcome, listing approved/rejected, plan upgraded, claim decisions

**Memberships (free + monthly via Stripe)**
- **Free** — 1 basic listing
- **Pro** ($19/mo default) — 5 listings, full storefront (gallery, video, social links),
  verified badge, analytics
- **Featured** ($49/mo default) — 15 listings, everything in Pro + priority placement
  (homepage + top of city/category pages)
- Stripe Checkout + billing portal + webhook (plan changes sync automatically);
  prices/IDs editable in Superadmin panel → Settings

**✨ AI fill (Claude API)**
- On the New Listing form, members paste their website URL and AI reads the site
  and pre-fills the whole form: name, category, city/state/country, contact details,
  description, tagline, social links, year founded. Members review, then submit.
- Powered by the Anthropic Claude API (`claude-opus-5`) with structured outputs —
  the extraction is schema-validated, then re-validated server-side against the
  directory's own categories/countries/states before it touches the form.
- Setup: paste your Anthropic API key in **Superadmin panel → Settings** (or in `app/config.php`
  → `'anthropic'`). Get one at https://platform.claude.com. No key = the feature
  quietly hides; everything else works.
- Safety: SSRF-guarded fetcher (public hosts only, redirect re-validation, 1 MB cap),
  10 fills/hour per member, the AI is instructed never to invent contact details.

**✍️ AI-written Profile** (staff only)
- "Write it with AI" under the Profile box in **Superadmin panel → Listings → edit**
  researches a business — the rest of its own site via web fetch, then whatever else
  is public via web search — and writes the 1,000–1,500 word Profile section that
  Pro and Premium storefronts show.
- Everything the directory already holds (services, products, category, city, review
  and social profiles) is handed over as established fact, so the profile agrees with
  the rest of the page instead of re-deriving it.
- Nothing is saved: the text lands in the edit box for staff to read and change, and
  saving is the same Save changes button as any other edit. Whatever is typed
  elsewhere on the form survives the round trip.
- The prompt forbids invented facts, contact details, prices, hours and unsupported
  superlatives, and requires sources to be tied to *this* business by domain, address
  or phone — same-name businesses in other towns are a real failure mode. Markdown is
  stripped on the way in, because the storefront prints the text as written.

**Member area** (`/account`)
- Dashboard, listing CRUD with plan limits, analytics (views / website clicks / calls,
  daily rollups), billing, profile settings

**Admin** (`/superadmin`) — separate staff sign-in at `/superadmin/login`, two levels
- **admin**: moderation queue (all listings launch as `pending`), members, reviews, categories
- **superadmin**: everything above + manage admin accounts + site settings (branding,
  pricing, Stripe price IDs)

## Directory layout

```
index.php        ← front controller + router (repo root = web root)
.htaccess          rewrites, HTTPS, caching, security headers
robots.txt         AI-crawler-friendly
assets/            css / js / img
uploads/           member-uploaded images (git-ignored)
app/             ← application (protected by its own .htaccess)
  config.php       your settings (copy from config.example.php — git-ignored)
  bootstrap.php    loads config, session, libraries
  lib/             db, auth, csrf, seo, geo, listings, plans, stripe, mailer
  controllers/     one file per route group
  views/           PHP templates (site, account, admin)
database/
  schema.sql       MySQL schema
  seed.sql         geography (249 countries, US states, starter cities) + categories
scripts/
  create_admin.php   create the first superadmin
  generate_seed.php  regenerate seed.sql from database/data/*.json
  seed_demo.php      populate popular cities with demo listings (--remove to undo)
```

## Install (cPanel / shared hosting)

1. Upload everything into `public_html` (the repo root **is** the web root —
   `app/`, `database/` and `scripts/` are protected by deny-all `.htaccess` files).
2. Create a MySQL database + user, then import `database/schema.sql`
   and `database/seed.sql` (phpMyAdmin → Import).
3. `cp app/config.example.php app/config.php` and fill in the site URL + DB credentials.
4. Create your superadmin:
   `php scripts/create_admin.php you@example.com "Your Name" yourpassword superadmin`
   (or run the INSERT by hand — the script prints usage).
5. Visit `/superadmin/login` (dedicated staff sign-in) → you land in `/superadmin`.
   Members sign up and sign in at `/signup` / `/login`.

## Stripe setup

1. In the Stripe dashboard create a **Product** with two recurring monthly **Prices**
   (Pro and Featured).
2. Put your API keys in `app/config.php` (`secret_key`, `publishable_key`).
3. Paste the two `price_…` IDs in **Superadmin panel → Settings**.
4. Add a webhook endpoint `https://yourdomain/stripe/webhook` for events
   `checkout.session.completed`, `customer.subscription.updated`,
   `customer.subscription.deleted`, and put its signing secret (`whsec_…`)
   in `app/config.php`.

## Local development

```bash
mysql -e "CREATE DATABASE monsterlist"
mysql monsterlist < database/schema.sql
mysql monsterlist < database/seed.sql
cp app/config.example.php app/config.php   # edit db creds + set site_url to http://localhost:8090
php scripts/create_admin.php super@example.com "Super" superpass123 superadmin
php -S localhost:8090 dev-router.php   # run from the repo root, or use Apache
```

`dev-router.php` (not committed) just needs to serve static files and otherwise
include `index.php`.

## Moderation model

Every new listing (and any edit that changes its name/description) goes to
`pending`. Admins approve or reject from the queue at `/superadmin`. This keeps the
directory clean — which is exactly what search engines and AI answers reward.
