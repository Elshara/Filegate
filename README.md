# Filegate

Filegate is a lightweight social networking application built with PHP and SQLite. It ships with everything you need for instant deployment so your community can start sharing updates right away.

## Features

- **User accounts** – Visitors can register, sign in, and manage secure sessions.
- **Personal profiles** – Every member gets a customizable profile with bio, location, and website fields.
- **Community feed** – Browse the latest posts from everyone in the network.
- **Posting** – Share updates (up to 500 characters) with rich formatting and automatic sanitization.
- **Appreciation actions** – Like and unlike posts to show support.
- **Settings dashboard** – Update profile information with immediate feedback.

## Project structure

```
public/           Web root containing the PHP front-end controllers
  assets/         Stylesheets
src/              Core helpers (database bootstrap, layout renderer)
data/             SQLite database file (created on first run)
```

## Requirements

- PHP 8.1 or newer with the SQLite3 extension enabled.
- Composer is **not** required—there are no external dependencies.

## Getting started

1. **Install dependencies** – Ensure PHP has the `pdo_sqlite` extension available.
2. **Start a development server:**

   ```bash
   php -S 0.0.0.0:8000 -t public
   ```

3. **Visit the app** – Navigate to [http://localhost:8000](http://localhost:8000) and create your first account.

The first request automatically provisions the SQLite database inside `data/social.db`. The file is ignored by Git so each environment keeps its own data.

## Deployment

You can deploy Filegate on any PHP-compatible host:

- Upload the repository and point the document root to the `public/` directory.
- Ensure the web server process can read and write to the `data/` directory so the SQLite database can be created.
- Optionally secure the application behind HTTPS using your host’s control panel or reverse proxy.

## Testing the experience

Because Filegate uses standard PHP forms, you can simulate a typical journey:

1. Register a new account at `/register.php`.
2. Post an update from the home feed.
3. Like your post to see the action counter update.
4. Visit `/profile.php` to review your personal timeline.
5. Adjust bio, location, and website details via `/settings.php`.

## Contributing

Pull requests are welcome! Please open an issue describing your idea before contributing major changes.

## License

MIT
