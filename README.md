# Utils

This package is used as utilities for the csgt packages.

| Package Version | Laravel UI | Cancerbero structure | Menu Table |
| --------------- | ---------- | -------------------- | ---------- |
| 5.0             | no         | names                | es         |
| 5.7             | no         | names                | en         |
| 6.0             | no         | names                | en         |
| 7.0             | yes        | names                | en         |
| 8.0             |            |                      | en         |

To render the menu, use the following snippet. This will auto-generate the required structure needed for the `csgt\menu` package.

```
{!! \Csgt\Utils\Menu::menu() !!}
```

## CI/CD Pipeline

Publish a GitHub Actions workflow that runs CI (code style, tests, frontend build) on every push and pull request, and deploys to production on the main branch only when CI passes.

```bash
php artisan make:csgtci
```

This creates `.github/workflows/ci.yml`. Everything is auto-detected so the bare command works without flags: the trigger/deploy branch is the repository's default branch, the PHP version is read from `composer.json` (`config.platform.php` or `require.php`), and the Node version from `.nvmrc` or `package.json` (`engines.node`). Pass flags only to override the detection:

```bash
php artisan make:csgtci --php=7.3 --node=12 --branch=master
```

| Option     | Default                                 | Description                                 |
| ---------- | --------------------------------------- | ------------------------------------------- |
| `--php`    | auto-detected (`composer.json`)         | PHP version used by the CI job              |
| `--node`   | auto-detected (`.nvmrc`/`package.json`) | Node version used by the CI job             |
| `--branch` | auto-detected                           | Branch that triggers CI and deployment      |
| `--force`  | —                                       | Overwrite the workflow if it already exists |

The `ci` job is universal: the test step skips when no `phpunit.xml` is present and becomes enforcing once tests exist, and the frontend build detects the Laravel Mix scripts (`production`/`prod`) with `build` as a fallback. If the project's old dependencies fail to resolve with Composer v2, change `tools: composer:v2` to `composer:v1` in the generated workflow.

The `deploy` job targets the nginx/php-fpm Docker stack published by `make:csgtdocker` on this version (services `nginx`, `php`, `scheduler`, `horizon`, `mysql`). The php image does not bundle Composer or Node: the deploy uses Composer inside the container when available and falls back to the host's, and builds frontend assets with the host's npm. It is a template — REVIEW it against how the project's server actually deploys before enabling it. Configure the repository secrets:

| Secret                       | Required | Description                                            |
| ---------------------------- | -------- | ------------------------------------------------------ |
| `PRODUCTION_HOST`            | yes      | Server hostname or IP                                  |
| `PRODUCTION_USER`            | yes      | SSH user                                               |
| `PRODUCTION_PATH`            | yes      | Absolute path of the project on the server             |
| `PRODUCTION_SSH_KEY`         | yes      | Unencrypted private deploy key (no passphrase)         |
| `PRODUCTION_SSH_PORT`        | no       | SSH port (defaults to 22)                              |
| `PRODUCTION_SSH_KNOWN_HOSTS` | no       | Pinned host key; prevents MITM (falls back to keyscan) |
| `PRODUCTION_HEALTHCHECK_URL` | no       | URL checked after deploy; deploy fails if not HTTP 200 |

CI runs are cancelled when superseded by a newer commit, but deployments are never cancelled mid-flight (concurrent pushes queue) to avoid leaving the server half-migrated.

## Documentation

Publish the standard project documentation:

```bash
php artisan make:csgtdocs
```

This creates three files from company-wide templates:

- `README.md` — stack, local setup, frequent commands, CI/CD summary, plus clearly marked `EDIT`/`TODO` sections for the project's business domain.
- `AGENTS.md` — guidance for AI coding agents (the cross-tool standard read by Codex, Gemini CLI, Cursor, etc.): stack, CSGT conventions (commits, migrations, ACL, CRUD pattern, testing checklist, generated files), environment commands, plus editable project-context sections.
- `CLAUDE.md` — a two-line pointer that imports `AGENTS.md` for Claude Code, so the guidance lives in a single tool-agnostic file.

Project name, repository (for the CI badge), PHP and Node versions are auto-detected (`composer.json`, git remote, `.nvmrc`). Existing files are never overwritten unless `--force` is passed. After generating, search for the `EDIT`/`TODO` markers and fill in the project-specific sections.

| Option    | Default                         | Description                                  |
| --------- | ------------------------------- | -------------------------------------------- |
| `--php`   | auto-detected (`composer.json`) | PHP version shown in the docs                |
| `--node`  | auto-detected (`.nvmrc`/`package.json`) | Node version shown in the docs       |
| `--force` | —                               | Overwrite the generated files if present     |
