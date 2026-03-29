# Configuration Schema Generation

Pixelfed ships an Artisan command that emits a machine-readable
[JSON Schema (Draft 2020-12)](https://json-schema.org/specification) document
covering every configurable option in the application.

## Running the command

```bash
php artisan config:schema
```

By default the schema is written to stdout. Useful flags:

| Flag | Description |
|---|---|
| `--pretty` | Pretty-print the JSON output |
| `--output=<path>` | Write to a file instead of stdout |
| `--filter=all` | Include every option (default) |
| `--filter=cached` | Only options editable at runtime via the admin panel |
| `--filter=env` | Only options settable via environment variables |

### Examples

```bash
# Pretty-print to terminal
php artisan config:schema --pretty

# Write to a file
php artisan config:schema --pretty --output=pixelfed-config.schema.json

# Only the env-var-backed options
php artisan config:schema --filter=env --output=pixelfed-env-options.schema.json

# Only runtime-editable options
php artisan config:schema --filter=cached --output=pixelfed-admin-options.schema.json
```

## Pre-generated schema

`pixelfed-config.schema.json` in the repository root is committed and kept in
sync by CI.  Regenerate it after any change to the sources described below:

```bash
php artisan config:schema --pretty --output=pixelfed-config.schema.json
```

CI will fail if the committed file is stale, catching drift before merge.

---

## How the schema is generated

The command merges three sources automatically.  **No manual list of options
is maintained in the command itself.**

### Source 1 — Config file scanning (env var, default, type)

`GenerateConfigSchema` spawns a clean PHP subprocess (without the Laravel
bootstrap) that evaluates each file listed in `CONFIG_FILES` against stubbed
versions of `env()`, `storage_path()`, `config()`, and similar helpers.

The `env()` stub returns a sentinel object rather than a real value.  After
evaluation the resulting array is walked recursively to build:

```
dot.notation.key → { env_var, default_value, type_cast }
```

Type casts such as `(int) env(...)` are detected by rewriting the source
before evaluation (`(int) env(` → `env_cast('int',`), preserving cast
information through the eval.

Running out-of-process is necessary because `env()` is already defined by
Laravel in the parent process and PHP does not permit function redefinition.

### Source 2 — `ConfigCacheService` (cached + sensitive flags)

`app/Services/ConfigCacheService.php` is parsed statically with a regex to
extract:

- **`$allowed`** — dot-notation keys editable at runtime via the admin panel
  → `x-config-cache: true`
- **`PROTECTED_KEYS`** — keys whose values are encrypted at rest
  → `x-sensitive: true`

### Source 3 — `config/schema-meta.php` (human-authored annotations)

`config/schema-meta.php` is a plain PHP `return [...]` file — the same format
as the other files in `config/` — that supplies the annotations that cannot be
derived from code.  Because it is ordinary PHP it supports `//` comments and
requires no additional dependencies.

```php
'pixelfed.max_photo_size' => [
    'description' => 'Maximum allowed size for a single photo upload, in kilobytes.',
    'group'       => 'media',
    'minimum'     => 1,
],
```

Supported fields:

| Field | Purpose |
|---|---|
| `description` | Human-readable explanation of the option |
| `group` | `x-group` category; overrides auto-inference when needed |
| `type` | JSON Schema type; only needed for DB-only keys (see [Known limitations](#known-limitations)) |
| `enum` | Array of allowed values |
| `minimum` / `maximum` | Numeric bounds |
| `format` | JSON Schema format hint, e.g. `"uri"` or `"email"` |

An entry in `schema-meta.php` is **optional**.  New options that appear in
`config/*.php` or `ConfigCacheService::$allowed` will appear in the schema
with correct type, default, env var, and inferred group even without one.
Adding an entry enriches the output with a description and constraints.

---

## Schema structure

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "title": "Pixelfed Instance Configuration",
  "type": "object",
  "properties": {
    "pixelfed.image_quality": {
      "description": "JPEG/WebP compression quality applied when optimizing images (1–100).",
      "type": "integer",
      "default": 80,
      "minimum": 1,
      "maximum": 100,
      "x-env": "IMAGE_QUALITY",
      "x-config-cache": true,
      "x-group": "media"
    }
  }
}
```

### Standard JSON Schema keywords

| Keyword | Purpose |
|---|---|
| `type` | JSON type (`boolean`, `integer`, `string`, `number`, `null`, or an array) |
| `default` | Value used when the option is not set |
| `description` | Human-readable description |
| `enum` | Allowed values |
| `minimum` / `maximum` | Numeric bounds |
| `format` | Format hint, e.g. `uri` or `email` |

### Extension keywords (`x-*`)

| Keyword | Type | Meaning |
|---|---|---|
| `x-env` | `string` | Environment variable name. Absent for DB-only options (no `env()` call). |
| `x-config-cache` | `boolean` | `true` = in `ConfigCacheService::$allowed`; editable at runtime without redeployment. |
| `x-group` | `string` | UI grouping category — see [Groups](#groups). |
| `x-sensitive` | `boolean` | `true` = secret value. Must not be logged, displayed in plaintext, or committed to version control. |

### Groups

| Group | Contents |
|---|---|
| `application` | App URL, environment, debug mode |
| `branding` | Instance name, descriptions, banner, custom CSS/JS |
| `registration` | Open/curated/closed registration, email verification |
| `media` | Upload size limits, image quality, optimization, HLS/P2P |
| `content` | Caption, bio, name, password, alt-text length limits |
| `accounts` | Storage quotas, user limits, autofollow, block/mute limits |
| `api` | OAuth, app registration, rate limits |
| `features` | Stories, Instagram import, Bouncer, embeds, legal notice |
| `discovery` | Landing page directory/explore, public timelines |
| `federation` | ActivityPub, authorized fetch, account migration, custom emoji |
| `captcha` | hCaptcha on login/register, trigger thresholds |
| `storage` | Cloud storage toggle, S3 / DigitalOcean Spaces credentials |
| `notifications` | Contact form, report emails |
| `general` | Options not matched by any of the above |

---

## Keeping the schema up to date

### Adding a new configurable option

1. Add `env('MY_VAR', $default)` to the appropriate `config/*.php` file.
2. If runtime-editable, add the dot-notation key to `ConfigCacheService::$allowed`.
3. If sensitive, add it to `ConfigCacheService::PROTECTED_KEYS`.
4. Optionally add an entry to `config/schema-meta.php` with a `description`
   and any constraint keywords.
5. Regenerate the committed schema:
   ```bash
   php artisan config:schema --pretty --output=pixelfed-config.schema.json
   ```

### Adding a new config file

Append the filename (without `.php`) to `CONFIG_FILES` in
`app/Console/Commands/GenerateConfigSchema.php`:

```php
private const CONFIG_FILES = [
    'app', 'pixelfed', 'federation', 'instance',
    'captcha', 'media', 'filesystems', 'autospam',
    'my_new_file', // ← add here
];
```

Then regenerate the committed schema.  If you forget, CI will catch it.

### Editing `config/schema-meta.php`

The file is a plain PHP array.  Use `//` comments freely to annotate entries
or group them.  The file must remain a pure data return (no side effects) since
it is loaded with `require`.  PHP syntax errors will cause `config:schema` to
fail, and the CI check will surface this immediately.

---

## Known limitations

The following edge cases in config file patterns are handled imperfectly.
They are rare in practice; this section documents the workarounds.

### 1. New config files must be registered manually

The extractor only scans files listed in `CONFIG_FILES`.  If a new `config/`
file is added to the project, its options will not appear in the schema until
the filename is appended to that constant.

**Workaround:** Add the filename to `CONFIG_FILES` and regenerate.

### 2. DB-only keys default to type `"string"`

Keys that appear in `ConfigCacheService::$allowed` but have no `env()` call
in any scanned config file (e.g. `account.autofollow`, `uikit.show_custom.css`)
have no default value to infer a type from, so the generator falls back to
`"string"`.

**Workaround:** Add a `type` entry in `config/schema-meta.php`:

```php
'account.autofollow' => [
    'description' => '...',
    'type'        => 'boolean',
],
```

Existing DB-only keys already have type overrides where needed.

### 3. Commented-out keys in `ConfigCacheService::$allowed` are included

The static regex that extracts `$allowed` matches any quoted string in the
array body, including entries inside `// comments`.  Currently
`// 'system.user_mode'` is the only such entry.  This causes a harmless extra
property in the schema with no description or env var.

**Workaround:** Either remove the comment or add a suppression entry in
`schema-meta.php`.  No action is required for it to be low-impact.

### 4. `env()` inside string concatenation loses the env var

```php
// config/filesystems.php
'url' => env('APP_URL') . '/storage',
```

The sentinel's `__toString()` returns `''`, so the result is the string
`'/storage'` — a literal, not a sentinel.  The key will not appear as
env-backed (`x-env` will be absent).

**Workaround:** Add an entry in `schema-meta.php` if the key needs to appear
in the schema.  This pattern is uncommon in Pixelfed's config files and the
affected keys are not in `ConfigCacheService::$allowed`.

### 5. Nested `env()` calls produce a `null` default

```php
// config/instance.php
'captcha_enabled' => env('INSTANCE_CUR_REG_CAPTCHA', env('CAPTCHA_ENABLED', false)),
```

The inner `env()` returns a sentinel, which becomes the `default` argument of
the outer call.  The generator detects this and records `null` for the default
rather than the inner env var's value.

**Impact:** The outer env var (`INSTANCE_CUR_REG_CAPTCHA`) is correctly
recorded; only the fallback default is lost.  The type is inferred as
`["string", "null"]` instead of `"boolean"`.  Add a `type` override in
`schema-meta.php` if precision matters.

### 6. Ternary conditions on `env()` pick only one branch

```php
// config/instance.php
'cached' => env('PF_NETWORK_TIMELINE') ? env('INSTANCE_NETWORK_TIMELINE_CACHED', false) : false,
```

The sentinel is truthy, so the extractor always follows the `true` branch and
records `INSTANCE_NETWORK_TIMELINE_CACHED`.  The outer env var
`PF_NETWORK_TIMELINE` is not recorded for this key.

**Impact:** The resulting schema entry is accurate for the common case (the
feature is enabled).  The outer guard variable is only visible via `.env.example`.
