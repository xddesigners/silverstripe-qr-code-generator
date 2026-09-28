# SilverStripe QR Code Generator

Create QR codes in the SilverStripe CMS that point at a short, stable redirect URL
(`/qr/{id}`) instead of the destination directly. Because the QR encodes the redirect
URL, you can repoint it to another page or external link **without reprinting the code**.
Optionally embeds a logo (from Site Settings) in the centre of the code.

## Features

- Manage QR codes in their own **QR Codes** admin section.
- Point each code at an **internal page** (tree dropdown) or an **external URL**.
- Codes encode a permanent short URL (`/qr/{id}`) that `301`-redirects to the target,
  so the printed code keeps working when the destination changes.
- Optional **logo** embedded in the middle of the code, configured once in Site Settings.
- **Download** the generated code from the edit form (PNG when a logo is used, SVG otherwise).
- Optional **per-page QR code** on every `SiteTree` page (off by default).

## Requirements

- SilverStripe CMS/Framework **6**
- PHP **8.3+**
- [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) `^4.4` (installed automatically)

> **SilverStripe 4?** Use the `ss4` branch: `composer require xddesigners/silverstripe-qr-code-generator:dev-ss4`

## Installation

```bash
composer require xddesigners/silverstripe-qr-code-generator
```

Then build the database and flush:

```
vendor/bin/sake db:build --flush
```

## Usage

### 1. Create a QR code

1. Open the **QR Codes** section in the CMS.
2. **Add** a QR code and set either an **Internal link** (page) or an **External link**.
   (If you leave the title empty it is derived from the linked page.)
3. Save. A preview of the code appears on the edit form, linking to its redirect URL.
4. Use **Download QR image** to save the file (PNG with a logo, otherwise SVG).

The code encodes `https://your-site/qr/{id}`. Visiting that URL `301`-redirects to the
current target, so you can change the destination later without reprinting.

### 2. Add a logo (optional)

1. Go to **Settings → QR Code Settings**.
2. Tick **Show QR Code with logo** and upload a **QRCode logo** (a square PNG works best).

When enabled, codes are rendered as PNG with the logo composited in the centre (using a
higher error-correction level so they stay scannable). Without a logo, codes render as
crisp SVG.

### 3. Per-page QR codes (optional)

A `SiteTree` extension can add a **QR Code** tab to every page, showing a code for that
page's own URL. It is disabled by default — enable it in your project config:

```yaml
# app/_config/qr-code.yml
SilverStripe\CMS\Model\SiteTree:
  extensions:
    - XD\QRCodeGenerator\Extensions\SiteTreeExtension
```

Run `dev/build?flush=all` afterwards.

## How it works

| Piece | Responsibility |
| --- | --- |
| `XD\QRCodeGenerator\Models\QRCode` | The QR code record (title, internal/external link) and image generation. |
| `XD\QRCodeGenerator\Controllers\QRCodeController` | Handles `/qr/{id}` and `301`-redirects to the target. |
| `XD\QRCodeGenerator\Admin\QRCodeAdmin` | The **QR Codes** CMS section. |
| `SiteConfigExtension` | Adds the logo on/off toggle and logo upload to Site Settings. |
| `SiteTreeExtension` | Optional per-page QR code (disabled by default). |
| `Image\QRImageWithLogo` | Composites the logo into the centre of the code. |

## QR URLs: token vs. ID

Each QR encodes a short redirect URL. By default the module uses an opaque,
non-guessable **token** — `/qr/{token}` — so the URLs can't be enumerated (nobody can
walk `/qr/1`, `/qr/2`, … to harvest every destination or find not-yet-distributed codes).

```yaml
XD\QRCodeGenerator\Models\QRCode:
  use_token: true      # default — non-enumerable /qr/{token}
  # token_length: 8    # base62; keep it short so the QR stays easy to scan
```

**Upgrading a site that already printed ID-based codes?** Those encode `/qr/{id}` and
token mode resolves tokens only, so switch back to legacy ID URLs:

```yaml
XD\QRCodeGenerator\Models\QRCode:
  use_token: false
```

Tokens are generated automatically and backfilled for existing records on `dev/build`.

## Translations

All CMS field labels, buttons and the admin menu are translatable via SilverStripe's i18n system (`lang/*.yml`). The module ships with:

- 🇬🇧 English (`en`)
- 🇳🇱 Dutch (`nl`)
- 🇩🇪 German (`de`)
- 🇮🇹 Italian (`it`)
- 🇪🇸 Spanish (`es`)
- 🇫🇷 French (`fr`)

Add another language by dropping a matching `lang/<locale>.yml` into the module (or your project).

## License

BSD-3-Clause. See [LICENSE](LICENSE).
