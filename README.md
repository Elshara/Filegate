# Filegate

Filegate is a profile-centred social application designed for shared hosting. Every asset is organised beneath the `/assets` tree so you can drop the project straight into `public_html` (or any web root) and start collaborating.

## Key principles

- **Flat-file persistence** – Users, posts, and settings are stored as JSON documents under `assets/json`, keeping deployment portable and backup-friendly.
- **Asset classification** – A manifest in `assets/json/datasets.json` labels each dataset as `static` or `dynamic` so the platform can route reads and writes to the correct store automatically.
- **Function-per-file PHP** – Each reusable routine lives in its own PHP file under `assets/php`, keeping entrypoints focused on only the logic they need.
- **Localized presentation** – Shared styles live in `assets/css`, while each page controller builds its markup on demand to keep the runtime light.
- **Delegated configuration** – Administrators can rename the network, expose or lock individual settings, and even swap out entire datasets without touching the filesystem.
- **HTML5-native content** – Profiles and posts accept a wide range of HTML5 elements, allowing creators to publish rich articles, galleries, conversations, or custom post types.
- **Attachment-friendly composer** – Members can upload files directly into `/assets/uploads/<extension>` with local previews and secure delivery via `media.php`.
- **Rich notifications** – Post activity queues email, browser, cookie, and file-cache notifications driven by JSON and XML templates with admin-controlled channels.
- **Themeable interface** – Palette presets live in flat files so administrators and members can rebrand Filegate from the browser without touching CSS.
- **Roadmap transparency** – A dedicated roadmap dataset tracks built, in-progress, and planned initiatives with browser-based management and feed summaries.
- **Poll-driven engagement** – Community polls live in flat files with delegated creation policies, visibility defaults, and multi-select support that admins manage entirely from the setup dashboard.
- **Self-hosted knowledge base** – Publish onboarding guides and reference articles locally so members can search, filter, and read without leaving Filegate.
- **Local bug triage** – A bug tracker dataset powers feed summaries and setup workflows so teams can capture, assign, and follow issues without external services.
- **Automation engine** – Flat-file automation rules respond to triggers with configurable conditions and actions that admins manage from the setup dashboard and review on the feed.

## Directory layout

```
assets/
  css/                Shared styles served directly from /assets
  js/                 Client runtime helpers (AJAX, previews, dataset viewers)
  json/               Flat-file datasets (users, posts, settings, manifests)
  php/                PHP entrypoints, controllers, and helpers (one function per file)
  xml/                XML descriptors (notification templates, XHTML prototypes)
  uploads/            Extension-based storage for user attachments
index.php              Authenticated feed controller (thin wrapper around assets/php)
login.php              Sign-in form bootstrapper
logout.php             Session terminator bootstrapper
media.php              Authenticated attachment streamer bootstrapper
post.php               Composer/editor wrapper
profile.php            Profile viewer & editor wrapper
register.php           Registration wrapper (first account becomes admin)
settings.php           Delegated settings workspace wrapper
setup.php              Administrative asset setup wrapper
toggle-like.php        AJAX like/unlike endpoint wrapper
dataset.php            Authenticated dataset viewer wrapper
feature-request.php    Feature request workspace wrapper
bug-report.php         Bug submission wrapper
knowledge.php          Knowledge base wrapper
page.php               Static page viewer wrapper
```

> **Note:** Each PHP wrapper at the project root simply loads its dedicated script from `assets/php`. The controllers encapsulate the real logic so administrators can configure and override behaviour without duplicating code across multiple directories.

The JavaScript helpers under `assets/js` expose a single function each (for example `fg_registerPostPreview`) so that pages can load only the behaviours they need. The client runtime is self-contained—no remote CDNs or APIs are required.

## First run

1. Upload the repository to your web root so `/assets` and the PHP entrypoints share the same directory.
2. Ensure the web server can create and write to `assets/json` (the app will create it automatically on first request).
3. Visit `/register.php` to create the first profile—this user is promoted to **admin** so they can delegate settings.
4. Head to `/settings.php` to review configurable options and dataset previews.

## Settings model

Settings are described in `assets/json/settings.json` with the following structure:

- `value` – Current value (string or JSON serialised string via the UI).
- `managed_by` – `admins`, `everyone`, `custom`, or `none`.
- `allowed_roles` – For `custom`, a list of roles (e.g. `moderator`) or specific people (`user:3`).
- `category` – The thematic group (branding, privacy, content, collaboration).

Admins can change both the value and the delegation policy for each entry. Non-admins may update a setting only when delegation grants them access.

The default dataset includes branding-oriented controls such as **Default Theme** (the preset applied to new visitors) and **Theme Personalisation Policy** (whether members can override palette tokens from their settings page).

## Asset configuration & setup

Every PHP, CSS, JSON, JS, XML, and HTTP controller is catalogued automatically through the asset configuration dataset. Administrators can open `/setup.php` to:

- review the generated defaults (enable/disable flags, mode selectors, and template variants) for each asset;
- assign which roles can personalise an asset and whether members may apply overrides from their own settings page;
- capture global or role-level overrides that cascade to every user; and
- create or clear user-specific values without editing flat files; and
- open the dataset manager to upload replacements, edit payloads, or regenerate seeded defaults for JSON and XML stores.
- curate **Content modules** built from the XML blueprints so teams can import reusable post types, customise field prompts, and guide publishers with step-by-step wizards.

The setup dashboard mirrors the `assets/php` convention by saving each change straight into the flat-file datasets (`asset_configurations.json` and `asset_overrides.json`). As new files are introduced, the manifest is refreshed automatically during bootstrap so the dashboard always reflects the real filesystem.

Members with permission to personalise assets will see an **Asset personalisation** section inside `/settings.php`. The UI surfaces the default, global, and role-derived values alongside the active value for each parameter so users can confidently tune their experience without breaking dependent assets.

## Page management and navigation

The setup dashboard now ships with a **Page Management** section dedicated to the flat-file `pages` dataset. Administrators can:

- review every published page in card form, complete with visibility, template keyword, and navigation status;
- edit titles, slugs, summaries, content, visibility, allowed roles, and navigation flags via labelled controls (no JSON editing required);
- delete obsolete pages safely with confirmation prompts; and
- draft brand-new pages from the browser, seeding content, template, and visibility defaults in one action.

Pages live in `assets/json/pages.json` and are seeded with a welcome page by default. The dataset is configurable like any other store—use the dataset manager to inspect the payload or reset to defaults. Each page honours the visibility model (`public`, `members`, or selected roles), and the global navigation automatically surfaces entries marked **Show in navigation** for both authenticated and guest visitors. Members still benefit from per-asset overrides because the renderer participates in the configuration manifest like every other asset.

Visit `/page.php` to see the published list. Linking directly to `/page.php?slug=welcome` (or any custom slug) renders the page using the stored template keyword, and administrators can create additional templates via existing asset tooling to extend the layout catalogue.

## Posting model

Posts are stored with HTML5-friendly bodies plus additional metadata:

- `summary` – Short notification copy used by templates, previews, and cards.
- `tags` – Array of comma-separated labels for filtering or search.
- `attachments` – Metadata for uploaded files living under `/assets/uploads/<extension>`.
- `template` – Selected layout name sourced from `template_options`.
- `format_options` – Rendering hints such as `content_format` (`html`, `xhtml`, `markdown`).
- `display_options` – Flags for showing statistics or embeds on a per-post basis.
- `notification_template` – XML/JSON template key for queued notifications.
- `notification_channels` – Channels requested in addition to admin defaults.
- `variables` – Key/value replacements merged into notification templates.
- `custom_type` – Optional label (e.g. `article`, `event`, `stream`).
- `privacy` – `public`, `connections`, or `private` per entry.
- `collaborators` – Usernames allowed to co-edit an entry.
- `conversation_style` – `standard`, `threaded`, or `broadcast`.
- `likes` – Array of user IDs who liked the post.
- `embeds` – Detected media metadata rendered inline when the rich embed policy allows it.
- `statistics` – Calculated metrics including word, character, heading, and embed counts.

The home feed honours privacy and collaborator rules, and collaborators can open posts in edit mode via `/post.php?post=<id>`.

Rich embeds are generated entirely locally using the templates in `assets/json/embed_providers.json`. Administrators can disable inline rendering globally through the **Rich Embed Policy** setting; the metadata is still stored with each post so embeds can reappear instantly when the policy is re-enabled.

Statistics follow the **Post Statistics Visibility** setting. When hidden, the values remain in the dataset for analysis without surfacing in the UI.

### Guided content modules

Teams that rely on structured templates can open the **Guided content modules** panel on the home feed to browse curated blueprints sourced from `assets/json/content_modules.json`. Each entry exposes its categories, wizard steps, profile prompts, CSS token references, optional micro/macro guidance, and a shortcut to `/post.php?module=<key>` where members can launch a guided composer.

The module composer preloads the module description, category tags, and type label, then renders every blueprint field as a labelled textarea alongside optional wizard-stage selectors. Micro guides spotlight individual publishing steps while macro guides outline team-level rollouts, so authors understand both immediate tasks and the broader workflow before publishing. Responses are stored directly with the post so the feed renders a dedicated module section—complete with field summaries, stage indicators, guidance summaries, and reference prompts—without touching external APIs.

Modules can also reference one another through relationship mappings. Each relationship records the connection type (`related`, `supports`, `records`, and so on), a target module key, an optional human-friendly label, and guidance about how the two entries collaborate. The feed, module composer, and published posts surface these connections so teams can hop between prerequisite templates, follow-up workflows, and companion formats without leaving Filegate.

Administrators may capture reusable checklists for each module so authors can mark important steps as they publish. Checklist items appear as inline tasks in the guided composer, persist on the post for later editing, and surface on the feed with completion states. Each surface now summarises progress with contextual badges (for example, *Not started*, *In progress*, or *Checklist complete*) plus completion counts so teams can scan status at a glance. Members can store their progress without editing flat files while administrators update task defaults directly from the setup dashboard, which shows the same summaries for every module card.

Administrators manage module lifecycle directly from **Setup → Content Modules**. Status flags (`active`, `draft`, `archived`) control whether a module appears on the feed, while visibility scopes (`everyone`, `members`, `admins`) and optional role filters keep specialist composers limited to the right teams. The setup dashboard now accepts granular micro and macro guide copy—either handwritten or imported from the XML blueprint library—so operators can codify both immediate prompts and long-form rollout checklists alongside module metadata. Draft and archived modules stay available to editors through their saved snapshots, so in-progress work is never lost while configuration evolves.

The creation and edit forms include a **Relationships** textarea that accepts `Type|Module key|Optional label|Optional description` entries—one per line. Filegate normalises each entry, ensuring module keys stay canonical while still preserving descriptive labels and notes. Leave the field empty to skip relationship mapping, or capture multiple lines to expose a full web of related templates in the UI.

## Managing datasets

Administrators can expand the **Dataset Management** section on `/setup.php` to edit or upload replacements for any dataset in the manifest. The summary panel highlights each store’s nature, format, size, and last update time, and the inline editor routes writes to the correct `static` or `dynamic` directory automatically. When a supported dataset (such as `users`, `posts`, `uploads`, `notifications`, `settings`, or `asset_overrides`) exposes defaults, the **Reset to defaults** button regenerates the seeded payload without touching the shell.

The latest release also introduces an `automations` dataset that stores trigger/condition/action metadata for Filegate's local workflow engine. Setup exposes dedicated cards for creating, editing, and deleting automations, while the member feed summarises status counts, priorities, and recent activity so everyone can see which routines are active. The automation dashboard now bundles an inline reference guide, advanced controls that stay tucked behind expandable panels, and stricter validation so malformed conditions or actions are caught before writes land. On the feed, automation summaries use sanitised status badges and call out when additional rules exist beyond the configured display limit.

Before any dataset write lands, Filegate now records the previous payload into the `asset_snapshots` store (`assets/json/asset_snapshots.json`). The setup dashboard surfaces the most recent captures for each dataset—complete with timestamps, the member who triggered the change, the original reason, and a trimmed preview. Administrators can create named snapshots on demand, restore an older payload in a single click, or prune redundant snapshots without leaving the browser.

Snapshot storage honours the dataset manifest’s static/dynamic split and enforces per-dataset limits so shared hosts stay tidy. Restoring a snapshot automatically records the current payload first, making every change reversible from the UI.

The dataset manager works alongside the dataset viewer endpoint (`/dataset.php`), which still honours the manifest’s `expose_via_api` flag. Sensitive stores such as `users` remain blocked, while reference data (for example `html5_elements`) and operational metadata (`settings`) are available for quick inspection directly from the browser.

The feed and composer use the same client runtime to fetch the HTML5 element reference on demand, provide live previews, and post likes asynchronously without full page reloads. All network calls terminate within the application—no remote APIs are required.

## Events

Events are stored in `assets/json/events.json`. Each record captures the title, summary, long-form description, visibility, status (`draft`, `scheduled`, `completed`, `cancelled` by default), scheduled start/end timestamps, timezone hints, location details (with optional URL), host IDs, collaborator IDs, tags, attachment references, and RSVP metadata (policy, limit, supporter IDs). Helper functions under `assets/php` handle creation, updates, and deletions while enforcing validation so the dataset stays well-formed.

Administrators manage scheduling from **Setup → Event planning**. The dashboard surfaces:

- status, upcoming/past, and RSVP totals so operators can scan overall engagement;
- a creation form with labelled controls for timing, visibility, timezone, hosts, collaborators, tags, attachments, and RSVP limits;
- editable cards for every event that expose the same controls inline, complete with quick delete actions; and
- policy-aware notices when event creation is limited to specific roles via the **Event creation policy** setting.

Member visibility honours the flat-file settings added to `settings.json`:

- **Event creation policy** (`event_policy`) delegates who can schedule events (`disabled`, `members`, `moderators`, `admins`).
- **Event default visibility** (`event_default_visibility`) establishes the baseline (`public`, `members`, `private`).
- **Event statuses** (`event_statuses`) defines the selectable lifecycle labels.
- **Event RSVP policy** (`event_rsvp_policy`) sets the default audience for RSVP collection.
- **Event default timezone** (`event_default_timezone`) seeds new schedules with a sensible offset.
- **Event feed display limit** (`event_feed_display_limit`) caps how many cards appear on the home feed.

The home feed now includes an **Events** panel that renders upcoming sessions first (falling back to recent past events if required). Cards highlight status, visibility, timing, timezone, location, RSVP progress, and hosts, while a summary row totals statuses and RSVP counts. Additional events beyond the configured display limit are noted, and administrators receive a direct link back to the setup dashboard (`/setup.php#event-manager`) for deeper management.

### Verifying the events experience

Use the built-in PHP development server to review the full workflow locally:

```bash
php -S 0.0.0.0:8000
```

1. Visit `http://127.0.0.1:8000/register.php` in your browser to seed the first administrator account.
2. Sign in at `http://127.0.0.1:8000/login.php` and open `http://127.0.0.1:8000/setup.php#event-manager` to manage records, defaults, and policies.
3. Return to `http://127.0.0.1:8000/index.php` to confirm upcoming events appear on the member feed.

For automated coverage, the following Playwright snippet signs in and captures screenshots of both the setup manager and the feed without relying on remote services:

```python
import asyncio, os
from playwright.async_api import async_playwright

async def main():
    os.makedirs('artifacts', exist_ok=True)
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        await page.goto('http://127.0.0.1:8000/login.php', wait_until='domcontentloaded')
        await page.fill('input[name="username"]', 'adminuser')
        await page.fill('input[name="password"]', 'StrongPass123!')
        await page.click('button[type="submit"]')
        await page.wait_for_load_state('networkidle')
        await page.goto('http://127.0.0.1:8000/setup.php#event-manager', wait_until='domcontentloaded')
        await page.screenshot(path='artifacts/setup-event-manager.png', full_page=False)
        await page.goto('http://127.0.0.1:8000/index.php', wait_until='domcontentloaded')
        await page.screenshot(path='artifacts/feed-events.png', full_page=False)
        await browser.close()

asyncio.run(main())
```

The generated screenshots (`artifacts/setup-event-manager.png` and `artifacts/feed-events.png`) help confirm that localised assets render correctly after each deployment.

## Polls

Poll data lives in `assets/json/polls.json`. Each record stores the question, description, status, visibility, whether multiple selections are allowed, a `max_selections` cap (with `0` representing unlimited picks for multi-select polls), option metadata (including supporter IDs and vote counts), owner role/user hints, timestamps, and optional expiry dates.

Administrators manage polls from **Setup → Poll catalogue**, where they can:

- inspect status totals, total response counts, and per-option vote/supporter breakdowns without touching JSON;
- edit questions, descriptions, status, visibility, expiry, ownership, and selectable options directly in the browser;
- toggle multi-select mode, enforce a maximum selection count, or close polls from the same form; and
- delete or create polls through dedicated forms that automatically normalise option lists and seed IDs.

Creation policies are delegated through the **Poll policy** setting (disabled, members, moderators, admins) alongside defaults for visibility and multi-select behaviour. Non-admins are blocked from accessing the setup dashboard, but the same flat-file dataset backs any future member-facing poll widgets. All saves are written via the poll helper functions in `assets/php`, so audit logging and dataset snapshots continue to track every change.

## Bug reports

Bug submissions live in `assets/json/bug_reports.json`. Each entry tracks the title, summary, rich details, status, severity, visibility, reporter, optional owner, tags, reproduction steps, affected versions, reference links, attachments, watcher IDs, and timestamps for creation, updates, and activity.

Administrators moderate issues from **Setup → Bug report manager**, which surfaces:

- status chips, severity breakdowns, and watcher totals so teams can understand workload at a glance;
- edit forms for ownership, status, severity, visibility, notes, reproduction steps, attachments, and tags without touching the filesystem;
- delete controls and creation forms that honour seeded defaults and record audit events automatically; and
- instant access to reference links, version metadata, and watcher IDs so follow-up conversations stay local.

Members see a condensed bug tracker on the feed that honours the submission policy configured in Settings. They can log new bugs (when allowed), browse summaries grouped by status and severity, expand details inline, and toggle watch/unwatch state via `/bug-report.php` without leaving Filegate.

## Roadmap tracking

Filegate seeds `assets/json/project_status.json` with representative milestones so operators can document what is built, in progress, or still planned without touching the filesystem. Administrators curate the roadmap from **Setup → Roadmap tracker**, where they can:

- capture entries with titles, categories, milestones, and summaries that explain the initiative;
- assign ownership to specific roles or profiles and define the desired status (`Built`, `In progress`, `Planned`, or `On hold`);
- record percentage progress and attach reference links for supporting datasets, templates, or documents; and
- review live status chips and audit trails before creating, updating, or deleting entries.

Every save updates the flat-file dataset, records an activity event, and keeps the roadmap in sync with asset permissions. Members see a condensed roadmap summary on the feed—complete with status counts, average progress, and the latest items—so teams can track what is shipping at a glance.

## Knowledge base

Knowledge articles live in `assets/json/knowledge_base.json`. Each record stores a slug, title, summary, HTML/XHTML body, visibility, status, template keyword, optional attachments, and an optional `category_id`. Categories are managed separately in `assets/json/knowledge_categories.json`, allowing you to organise guides into browsable collections without editing PHP.

Administrators curate entries from **Setup → Knowledge base**, where they can:

- review status totals, top tags, category usage, and article metadata from a single dashboard;
- edit titles, content, visibility, templates, categories, and attachments through browser forms;
- assign authors, adjust tags, archive articles, or move them between categories without touching JSON; and
- create new guides with sensible defaults seeded from `assets/php/default_knowledge_base_dataset.php` and `assets/php/default_knowledge_categories_dataset.php`.

The same setup screen also exposes category management cards so admins can add, reorder, hide, or delete categories—complete with audit logging and automatic detachment from affected articles.

Members and guests can browse `/knowledge.php`, filter by tag or category, run keyword searches, and open individual articles rendered by `assets/php/render_knowledge_base.php`. The feed surfaces a summary panel that honours visibility rules, while admins can tune behaviour (default tag, default category, tag cloud visibility, category filter availability, listing limits) via asset configuration overrides.

## Feature request board

Ideas, enhancements, and operational chores sit alongside roadmap items in `assets/json/feature_requests.json`. Administrators open **Setup → Feature request catalogue** to:

- review status and priority chips that summarise how many ideas are open, researching, planned, in progress, completed, or declined;
- edit individual requests with forms for titles, summaries, detailed briefs, visibility, impact, effort, tags, requestor/owner assignments, and supporter lists without touching the file system;
- queue internal notes and supporter IDs so moderators can acknowledge interest while keeping sensitive metadata out of the feed; and
- create or delete requests directly from the browser while the activity log records every change.

Statuses, priorities, the submission policy, and default visibility are all configurable from the Settings dataset. Members see a condensed feature request panel on the feed with supporter buttons, status badges, and reference links—no external API required.

## Changelog management

Releases, fixes, and operational changes live in `assets/json/changelog.json`. Administrators can open **Setup → Changelog** to:

- publish entries with titles, summaries, long-form bodies, and highlight toggles so important updates stand out on the feed,
- categorise each change as a release, improvement, fix, announcement, or breaking change, and control whether it is visible to everyone, signed-in members, or administrators,
- attach related roadmap entry IDs, comma-separated tags, and supporting links to connect the update with context across datasets, and
- schedule entries with a specific published timestamp or keep them unpublished until they are ready.

The setup dashboard lists existing entries with edit and delete controls, while the feed renders the most recent updates—respecting visibility, publication dates, and highlight states—without leaving the application or calling remote APIs.

## Activity log

Every dataset mutation, snapshot operation, and setup action is written to the flat-file `activity_log` dataset. The **Activity log** section on `/setup.php` lets administrators:

- filter entries by dataset, category, action, or user (by username, role, or numeric ID);
- adjust how many records appear at once without touching the filesystem; and
- inspect the raw payload and context for each event, complete with request metadata and trigger notes.

The activity history is captured before and after snapshot restores, dataset resets, override changes, and API requests routed through the controllers. Logs stay on disk alongside other dynamic datasets so hosts without server access can still audit changes directly from the browser.

## Themes and palette personalisation

Colour palettes live entirely in flat files so hosts without shell access can still rebrand Filegate.

- `assets/json/themes.json` stores named presets, each of which defines values for the published theme tokens.
- `assets/json/theme_tokens.json` documents every token, its description, default colour, and the CSS variable the runtime will update.
- Administrators manage presets from `/setup.php`. Each theme card includes live previews, reset helpers, and buttons for setting or removing the default palette.
- Creating a new theme happens directly in the browser—enter a key, adjust the token colours, and Filegate writes the dataset for you.
- Members can opt into personal palettes from `/settings.php` when the **Theme Personalisation Policy** setting is enabled. Changes apply instantly thanks to the `fg_registerThemePreview` helper.
- Reset buttons on both the admin and member views restore stored values or the static defaults so experiments are always reversible.

## Notifications and delivery

Filegate keeps notification metadata in flat files so delivery agents can operate without remote APIs.

- `assets/json/notification_channels.json` defines the available transports (email, browser push, cookie banner, file-cache) and their capabilities.
- `assets/xml/notification_templates.xml` stores channel-specific subjects and bodies; admins may extend it with additional `<template>` nodes.
- `assets/json/notifications.json` is the delivery queue written by post creation and updates.
- `assets/uploads/<extension>` holds binary attachments that can be referenced inside notification payloads.

The **Default Notification Channels** and **Notification Cache Driver** settings in `/settings.php` decide which transports are queued automatically and whether file-based caching is active. For Apache hosts you can add caching or cookie headers via `.htaccess`; for Nginx, mirror those directives inside your server block (e.g. caching `/assets/uploads/` and exposing the `media.php` download endpoint).

A local service worker or CRON job can inspect `notifications.json` and the per-channel cache files generated under `/assets/uploads/file-cache/` to send emails or push payloads using platform-specific tooling.

## Development

Start a local PHP server from the project root:

```bash
php -S 0.0.0.0:8000
```

Then visit [http://localhost:8000](http://localhost:8000). Flat-file datasets are created automatically under `assets/json` once the app receives traffic.

## Testing

Run a syntax check over every PHP file:

```bash
find assets -name '*.php' -print0 | xargs -0 -n1 php -l
find . -maxdepth 1 -name '*.php' -print0 | xargs -0 -n1 php -l
```

## License

MIT

### Locale management

Filegate ships with a `translations` dataset that stores interface strings for each supported locale. Administrators can open **Setup → Locale management** to:

- register new translation tokens, document where they are used, and cascade default values across locales,
- add new locales from an existing baseline,
- edit strings for each registered token with labelled text areas,
- switch the fallback locale used when translations are missing, and
- retire locales that are no longer required.

The default locale applied to new members is governed by the `default_locale` setting, while the `locale_personalisation_policy` setting determines whether members may choose their own language from the Settings page. Members and visitors can also pick a locale during registration whenever the policy allows personalisation.

