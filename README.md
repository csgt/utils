# Utils

This package is used as utilities for the csgt packages.

| Package Version | Laravel UI | Cancerbero | Crud version | Menu Table | AdminLTE    | Vue | PHP | Webserver |
| --------------- | ---------- | ---------- | ------------ | ---------- | ----------- | --- | --- | --------- |
| 5.0             | no         | names      | ?            | es         | ?           | 2   | ?   | nginx     |
| 6.0             | no         | names      | ?            | en         | ?           | 2   | ?   | nginx     |
| 5.7             | no         | names      | ?            | en         | ?           | 2   | ?   | nginx     |
| 7.0             | yes        | names      | ?            | en         | ?           | 2   | ?   | nginx     |
| 8.0             | yes        | names      | 7            | en         | 3.2.0-beta  | 2   | ?   | nginx     |
| 9.0             | yes        | names      | 8            | en         | 4.0.0-alpha | 3   | 8.1 | octane    |
| 10.0            | yes        | names      | 8            | en         | 4.0.0-alpha | 3   | 8.2 | octane    |

To render the menu, use the following snippet. This will auto-generate the required structure needed for the `csgt\menu` package.

## CI/CD Pipeline

Publish a GitHub Actions workflow that runs CI (code style, tests, frontend build) on every push and pull request, and deploys to production on the main branch only when CI passes.

```bash
php artisan make:csgtci
```

This creates `.github/workflows/ci.yml`. The trigger/deploy branch is auto-detected (the repository's default branch), so it works for both `master` (legacy) and `main` (newer) projects. Set the PHP and Node versions to match the project:

```bash
php artisan make:csgtci --php=8.2 --node=18 --branch=master
```

| Option     | Default       | Description                                        |
| ---------- | ------------- | -------------------------------------------------- |
| `--php`    | 8.3           | PHP version used by the CI job                     |
| `--node`   | 20            | Node version used by the CI job                    |
| `--branch` | auto-detected | Branch that triggers CI and deployment             |
| `--force`  | —             | Overwrite the workflow if it already exists        |

The `ci` job is universal and rarely needs editing. Projects without tests pass CI harmlessly: the test step skips when no `phpunit.xml` is present and becomes enforcing once tests exist.

The `deploy` job is project-specific (host, Docker, Octane, etc.). Configure the repository secrets:

| Secret                        | Required | Description                                             |
| ----------------------------- | -------- | ------------------------------------------------------- |
| `PRODUCTION_HOST`             | yes      | Server hostname or IP                                   |
| `PRODUCTION_USER`             | yes      | SSH user                                                |
| `PRODUCTION_PATH`             | yes      | Absolute path of the project on the server              |
| `PRODUCTION_SSH_KEY`          | yes      | Unencrypted private deploy key (no passphrase)          |
| `PRODUCTION_SSH_PORT`         | no       | SSH port (defaults to 22)                               |
| `PRODUCTION_SSH_KNOWN_HOSTS`  | no       | Pinned host key; prevents MITM (falls back to keyscan)  |
| `PRODUCTION_HEALTHCHECK_URL`  | no       | URL checked after deploy; deploy fails if not HTTP 200  |

CI runs are cancelled when superseded by a newer commit, but deployments are never cancelled mid-flight (concurrent pushes queue) to avoid leaving the server half-migrated.
