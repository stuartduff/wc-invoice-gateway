# Releasing to GitHub

How to ship a new version of **Invoice Payment Gateway for WooCommerce** to GitHub, including an automated clean plugin zip on [Releases](https://github.com/stuartduff/wc-invoice-gateway/releases).

## Remotes

This repo may have more than one remote. GitHub is:

```text
git@github.com:stuartduff/wc-invoice-gateway.git
```

Check remotes:

```bash
git remote -v
```

If `github` is missing:

```bash
git remote add github git@github.com:stuartduff/wc-invoice-gateway.git
```

Push code to GitHub:

```bash
git push github master
```

## Before you tag

### 1. Bump the version everywhere it matters

Update the new version (example: `2.0.4`) in:

| File | What to change |
| --- | --- |
| `wc-invoice-gateway.php` | Plugin header `Version`, and WP / WC “Requires” / “Tested up to” if needed |
| `readme.txt` | `Stable tag`, WP requires/tested headers, short description if needed, **Changelog** entry |
| `README.md` | Matching changelog entry (dev-facing; not shipped in the release zip) |

Changelog entry in `readme.txt` must use this shape so CI can extract release notes:

```text
= 2.0.4 - DD/MM/YY =
* Fix - Short description of the change.
```

The heading must start with `= {version} ` (version matching the git tag).

### 2. Rebuild block assets if you changed JS

Source lives in `resources/js/frontend/`. Runtime assets live in `assets/js/frontend/`.

If you changed anything under `resources/`:

```bash
npm install
npm run build
```

Commit the updated files under `assets/` with your release commit.

### 3. Commit and push `master`

```bash
git add -A
git status
git commit -m "= 2.0.4 - DD/MM/YY = * Fix - Short description of the change."
git push github master
```

Do **not** tag until `master` on GitHub has the version bump commit.

## Create the release (tag)

Tags use **no `v` prefix** (e.g. `2.0.4`, not `v2.0.4`). The workflow trigger is `*.*.*`.

```bash
git tag 2.0.4
git push github 2.0.4
```

That starts [`.github/workflows/release.yml`](../.github/workflows/release.yml), which:

1. Builds `wc-invoice-gateway-2.0.4.zip` via `git archive` (respects `.gitattributes` `export-ignore`)
2. Pulls release notes from the matching `readme.txt` changelog section
3. Creates a GitHub Release and attaches the zip

Watch progress: **Actions** tab on the repo, or:

```bash
gh run list --repo stuartduff/wc-invoice-gateway --workflow=release.yml
gh release view 2.0.4 --repo stuartduff/wc-invoice-gateway
```

Release page: https://github.com/stuartduff/wc-invoice-gateway/releases

## What is in the release zip

**Included (runtime plugin):**

- `wc-invoice-gateway.php`
- `index.php`
- `readme.txt`
- `assets/`
- `includes/`
- `languages/`

**Excluded (development / tooling)** via [`.gitattributes`](../.gitattributes):

- `bin/`
- `resources/`
- `node_modules/` (not tracked)
- `.gitignore`, `.gitattributes`, `.github/`
- `package.json`, `package-lock.json`
- `phpcs.xml`, `webpack.config.js`
- `README.md`, `docs/`

Distribute the uploaded asset **`wc-invoice-gateway-{version}.zip`**. Prefer that over GitHub’s automatic “Source code” archive.

## Folder layout reminder

| Path | Role |
| --- | --- |
| `resources/` | Webpack / Blocks JS **source** (edit here) |
| `assets/` | Built JS WordPress loads (ship this) |
| `includes/` | PHP (gateway + Blocks integration) |

## Checklist

```text
- [ ] Version bumped in wc-invoice-gateway.php and readme.txt
- [ ] Changelog entry added (= X.Y.Z - date =)
- [ ] Blocks assets rebuilt if resources/ changed
- [ ] Changes committed and pushed to github master
- [ ] Tag X.Y.Z created and pushed to github
- [ ] Actions release workflow succeeded
- [ ] Release zip on GitHub contains only runtime files
```

## Troubleshooting

**Release workflow did not run**  
Confirm the tag matches `*.*.*` (e.g. `2.0.4`) and was pushed to `github`, not only created locally (`git push github 2.0.4`).

**Release notes are empty / generic**  
The `readme.txt` section heading must match the tag, e.g. `= 2.0.4 - 01/01/26 =`.

**Zip still contains dev files**  
Ensure exclusions are listed in `.gitattributes` and that file is committed on the tagged revision. `export-ignore` only applies to paths known to git at that commit.

**Need to replace a bad release**  
Delete the GitHub release and tag, fix `master`, retag, and push the tag again. Avoid rewriting published tags people may already have downloaded unless you must.
