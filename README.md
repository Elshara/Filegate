# Filegate

Filegate is a profile-centred social application designed for shared hosting. Every asset is organised beneath the `/assets` tree so you can drop the project straight into `public_html` (or any web root) and start collaborating.

## Key principles

- **Flat-file persistence** – Users, posts, and settings are stored as JSON documents under `assets/json/dynamic`, keeping deployment portable and backup-friendly.
- **Asset classification** – A manifest in `assets/json/static/datasets.json` labels each dataset as `static` or `dynamic` so the platform can route reads and writes to the correct store automatically.
- **Function-per-file PHP** – Each reusable routine lives in its own PHP file under `assets/php/{global|pages|public}` so entrypoints load only the logic they need.
- **Localized presentation** – Shared styles live in `assets/css`, while each page controller builds its markup on demand to keep the runtime light.
- **Delegated configuration** – Administrators can rename the network, expose or lock individual settings, and even swap out entire datasets without touching the filesystem.
- **HTML5-native content** – Profiles and posts accept a wide range of HTML5 elements, allowing creators to publish rich articles, galleries, conversations, or custom post types.
- **Attachment-friendly composer** – Members can upload files directly into `/assets/uploads/<extension>` with local previews and secure delivery via `media.php`.
- **Rich notifications** – Post activity queues email, browser, cookie, and file-cache notifications driven by JSON and XML templates with admin-controlled channels.

## Directory layout

```
assets/
  css/global/          Shared styles served directly from /assets
  js/global/           Client runtime helpers (AJAX, previews, dataset viewers)
  json/static/         Manifested metadata (dataset registry, other static assets)
  json/dynamic/        Flat-file datasets (users, posts, settings)
  xml/static/          XML descriptors (notification templates, XHTML prototypes)
  xml/dynamic/         Reserved for future XML datasets
  php/global/          Global helper functions (one function per file)
  php/pages/           Localised renderers for feed, settings, and profile views
  php/public/          HTTP controllers that back the public entrypoints
  uploads/             Extension-based storage for user attachments
index.php              Authenticated feed controller (thin wrapper around assets/php/public)
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
```

> **Note:** Each PHP wrapper at the project root simply loads its dedicated controller from `assets/php/public`. The controllers encapsulate the real logic so administrators can configure and override behaviour without duplicating code across multiple directories.

The JavaScript helpers under `assets/js/global` expose a single function each (for example `fg_registerPostPreview`) so that pages can load only the behaviours they need. The client runtime is self-contained—no remote CDNs or APIs are required.

## First run

1. Upload the repository to your web root so `/assets` and the PHP entrypoints share the same directory.
2. Ensure the web server can create and write to `assets/json` (the app will create it automatically on first request).
3. Visit `/register.php` to create the first profile—this user is promoted to **admin** so they can delegate settings.
4. Head to `/settings.php` to review configurable options and dataset previews.

## Settings model

Settings are described in `assets/json/dynamic/settings.json` with the following structure:

- `value` – Current value (string or JSON serialised string via the UI).
- `managed_by` – `admins`, `everyone`, `custom`, or `none`.
- `allowed_roles` – For `custom`, a list of roles (e.g. `moderator`) or specific people (`user:3`).
- `category` – The thematic group (branding, privacy, content, collaboration).

Admins can change both the value and the delegation policy for each entry. Non-admins may update a setting only when delegation grants them access.

## Asset configuration & setup

Every PHP, CSS, JSON, JS, XML, and HTTP controller is catalogued automatically through the asset configuration dataset. Administrators can open `/setup.php` to:

- review the generated defaults (enable/disable flags, mode selectors, and template variants) for each asset;
- assign which roles can personalise an asset and whether members may apply overrides from their own settings page;
- capture global or role-level overrides that cascade to every user; and
- create or clear user-specific values without editing flat files; and
- open the dataset manager to upload replacements, edit payloads, or regenerate seeded defaults for JSON and XML stores.

The setup dashboard mirrors the `assets/php/global` convention by saving each change straight into the flat-file datasets (`asset_configurations.json` and `asset_overrides.json`). As new files are introduced, the manifest is refreshed automatically during bootstrap so the dashboard always reflects the real filesystem.

Members with permission to personalise assets will see an **Asset personalisation** section inside `/settings.php`. The UI surfaces the default, global, and role-derived values alongside the active value for each parameter so users can confidently tune their experience without breaking dependent assets.

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

Rich embeds are generated entirely locally using the templates in `assets/json/static/embed_providers.json`. Administrators can disable inline rendering globally through the **Rich Embed Policy** setting; the metadata is still stored with each post so embeds can reappear instantly when the policy is re-enabled.

Statistics follow the **Post Statistics Visibility** setting. When hidden, the values remain in the dataset for analysis without surfacing in the UI.

## Managing datasets

Administrators can expand the **Dataset Management** section on `/setup.php` to edit or upload replacements for any dataset in the manifest. The summary panel highlights each store’s nature, format, size, and last update time, and the inline editor routes writes to the correct `static` or `dynamic` directory automatically. When a supported dataset (such as `users`, `posts`, `uploads`, `notifications`, `settings`, or `asset_overrides`) exposes defaults, the **Reset to defaults** button regenerates the seeded payload without touching the shell.

The dataset manager works alongside the dataset viewer endpoint (`/dataset.php`), which still honours the manifest’s `expose_via_api` flag. Sensitive stores such as `users` remain blocked, while reference data (for example `html5_elements`) and operational metadata (`settings`) are available for quick inspection directly from the browser.

The feed and composer use the same client runtime to fetch the HTML5 element reference on demand, provide live previews, and post likes asynchronously without full page reloads. All network calls terminate within the application—no remote APIs are required.

## Notifications and delivery

Filegate keeps notification metadata in flat files so delivery agents can operate without remote APIs.

- `assets/json/static/notification_channels.json` defines the available transports (email, browser push, cookie banner, file-cache) and their capabilities.
- `assets/xml/static/notification_templates.xml` stores channel-specific subjects and bodies; admins may extend it with additional `<template>` nodes.
- `assets/json/dynamic/notifications.json` is the delivery queue written by post creation and updates.
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
