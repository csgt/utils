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

Publish a GitHub Actions workflow that runs CI (code style, tests, frontend build) on every push and pull request, and deploys to production on `main` only when CI passes.

```bash
php artisan publish:ci
```

This creates `.github/workflows/ci.yml`. Set the PHP and Node versions to match the project:

```bash
php artisan publish:ci --php=8.2 --node=18
```

| Option    | Default | Description                                  |
| --------- | ------- | -------------------------------------------- |
| `--php`   | 8.3     | PHP version used by the CI job               |
| `--node`  | 20      | Node version used by the CI job              |
| `--force` | —       | Overwrite the workflow if it already exists  |

The `ci` job is universal and rarely needs editing. The `deploy` job is project-specific: set the `PRODUCTION_*` repository secrets and adjust the deployment commands to match the project (host, Docker, Octane, etc.).
