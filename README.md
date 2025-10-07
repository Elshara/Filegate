# Filegate

Filegate is a profile-centred social application designed for shared hosting. Every asset is organised beneath the `/assets` tree so you can drop the project straight into `public_html` (or any web root) and start collaborating.

## Key principles

- **Flat-file persistence** – Users, posts, and settings are stored as JSON documents under `assets/json/dynamic`, keeping deployment portable and backup-friendly.
- **Asset classification** – A manifest in `assets/json/static/datasets.json` labels each dataset as `static` or `dynamic` so the platform can route reads and writes to the correct store automatically.
- **Function-per-file PHP** – Each reusable routine lives in its own PHP file under `assets/php/{global|pages}` so pages load only the logic they need.
- **Localized presentation** – Shared styles live in `assets/css`, while each page controller builds its markup on demand to keep the runtime light.
- **Delegated configuration** – Administrators can rename the network, expose or lock individual settings, and even swap out entire datasets without touching the filesystem.
- **HTML5-native content** – Profiles and posts accept a wide range of HTML5 elements, allowing creators to publish rich articles, galleries, conversations, or custom post types.

## Directory layout

```
assets/
  css/global/          Shared styles (mirrored to public/assets for direct serving)
  json/static/         Manifested metadata (dataset registry, other static assets)
  json/dynamic/        Flat-file datasets (users, posts, settings)
  php/global/          Global helper functions (one function per file)
  php/pages/           Localised renderers for feed, settings, and profile views
public/
  assets/css/global/   Web-exposed copy of shared styles
  index.php            Authenticated feed controller
  login.php            Sign-in form
  logout.php           Session terminator
  post.php             Composer/editor for posts
  profile.php          Profile viewer & editor
  register.php         Registration form (first account becomes admin)
  settings.php         Delegated settings workspace
```

> **Note:** The PHP files always reference the canonical copies under `assets/`. The stylesheet is duplicated into `public/assets` so shared hosts can serve it directly without extra rewrite rules. If you customise the CSS, re-copy it (or automate the copy) before deploying.

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

## Posting model

Posts are stored with HTML5-friendly bodies plus additional metadata:

- `custom_type` – Optional label (e.g. `article`, `event`, `stream`).
- `privacy` – `public`, `connections`, or `private` per entry.
- `collaborators` – Usernames allowed to co-edit an entry.
- `conversation_style` – `standard`, `threaded`, or `broadcast`.
- `likes` – Array of user IDs who liked the post.

The home feed honours privacy and collaborator rules, and collaborators can open posts in edit mode via `/post.php?post=<id>`.

## Managing datasets

Administrators can replace the entire contents of `users`, `posts`, or `settings` by pasting JSON into the dataset form on `/settings.php`. The manifest ensures each dataset is routed to the right `static` or `dynamic` store, enabling migrations without SSH access while keeping a clear audit of every dataset’s purpose.

## Development

Start a local PHP server from the project root:

```bash
php -S 0.0.0.0:8000 -t public
```

Then visit [http://localhost:8000](http://localhost:8000). Flat-file datasets are created automatically under `assets/json` once the app receives traffic.

## Testing

Run a syntax check over every PHP file:

```bash
find assets public -name '*.php' -print0 | xargs -0 -n1 php -l
```

## License

MIT

