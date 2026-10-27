# Digital Business Contact Generator

WordPress plugin that creates shareable digital business contact cards for people across multiple organisations (BSS, HIA, Hope Village, SOL).

**Version:** 1.4  
**Author:** Marnelle Apat

---

## What it does

Admins create a **Person** under **Digital Card Generator**. Each person gets a public card page with profile photo, role, quote, phone, email, QR code, and a downloadable vCard (`.vcf`). Cards can be branded per organisation and reached via organisation-specific URLs.

---

## Use cases

| Use case | How the plugin helps |
| --- | --- |
| Staff / volunteer digital name cards | Publish a person card and share the permalink instead of a printed card |
| Multi-brand / multi-org sites | Assign an organisation so URL, PHP template, and design pack match that brand |
| Event networking | Recipients scan the QR or open the card to save contact details via `.vcf` |
| Site migration / staging → production | Export all cards as JSON and import on another WordPress install |
| Brand refresh without code changes | Adjust logos, colours, fonts, and spacing per organisation in the Profile Customizer |

---

## Features

### Digital Card Generator (`person_details`)

- Custom post type with Block Editor support and a locked starter template
- Fields guided in the editor: profile image, position/designation, quote, QR shortcode, vCard URL, phone, email
- Public permalinks (default): `/id/{slug}`
- Featured-image labels remapped to **Profile Image**

### Organisations & URLs

Each person can be assigned an organisation. Permalinks use a matching prefix:

| Organisation | URL prefix |
| --- | --- |
| Default (BSS) | `/id/{slug}` |
| HIA | `/hia/{slug}` |
| Hope Village | `/hopevillage/{slug}` |
| SOL | `/sol/{slug}` |

### Card templates (`card_template`)

- One design pack per organisation (1:1)
- Maps organisation → PHP template under `/template/`
- Built-in templates:
  - `single-person_details_BSS.php`
  - `single-person_details_HIA.php`
  - `single-person_details_hopevillage.php`
  - `single-person_details_SOL.php`
- Default templates are seeded on activation

### Profile Customizer

- Admin UI under **Digital Card Generator → Profile Customizer**
- Per-organisation design settings: background, logos, colours, gradients, typography, box shadows, link colours, and more
- Advanced defaults and help/debug tabs included
- Customizer lives inside this plugin (no separate customizer plugin required)

### Import / Export

- **Digital Card Generator → Import / Export**
- Export all cards (except trash) as JSON
- Import with optional:
  - update existing cards by slug
  - sideload images and `.vcf` files into the Media Library and rewrite URLs

---

## Requirements

| Requirement | Notes |
| --- | --- |
| WordPress | Block Editor (Gutenberg) required for the locked person template |
| PHP | Compatible with typical WordPress hosting (no Composer packages) |
| Permalinks | Pretty permalinks recommended so org rewrite rules work |

On activation the plugin registers post types, seeds card templates, and flushes rewrite rules.

---

## Dependencies

### Soft dependency (recommended)

**[QR code MeCard/vCard generator](https://wordpress.org/plugins/)** (`wp-qrcode-me-v-card`)

Cards expect this shortcode in person content:

```text
[qr-code-contact-information-card card_id="42"]
```

Without that plugin installed and active, the QR block will not render. vCard download still works if a `.vcf` URL is set in the person content.

### External assets (loaded by front-end templates)

- Google Fonts (script / decorative typefaces)
- Font Awesome 6.5 (cdnjs) for icons

### WordPress core only otherwise

No Composer, npm, ACF, WooCommerce, or other hard plugin requirements. Media sideload on import uses WordPress core admin includes (`file.php`, `media.php`, `image.php`).

---

## Installation

1. Copy this folder into `wp-content/plugins/Digital_Business_Contact_Generator/`
2. Activate **Digital Business Contact Generator** in **Plugins**
3. (Recommended) Activate **QR code MeCard/vCard generator** for QR shortcodes
4. Confirm permalinks are not set to “Plain”, then visit **Settings → Permalinks → Save** if org URLs 404

---

## Quick start

1. Go to **Digital Card Generator → Add New Person**
2. Enter the person’s name (title)
3. Fill the locked block template (photo, role, quote, QR shortcode, vCard URL, phone, email)
4. Set **Organisation** in the person meta box
5. Publish and open the public URL (e.g. `/id/jane-doe` or `/hia/jane-doe`)
6. Optionally open **Profile Customizer** and tune that organisation’s design

---

## Plugin structure

```text
Digital_Business_Contact_Generator/
├── Digital_Business_Contact_Generator.php   # Bootstrap, CPT, template routing
├── includes/
│   ├── class-bss-business-contact-organization.php
│   ├── class-bss-business-contact-card-template.php
│   ├── class-bss-business-contact-customizer.php
│   └── class-bss-business-contact-import-export.php
├── admin/
│   ├── import-export-page.php
│   └── customizer/                          # Profile Customizer UI + assets
├── template/                                # Front-end card layouts per org
└── css/style.css                            # Shared card styles
```

---

## Admin menu overview

Under **Digital Card Generator**:

- **All People / Add New Person** — create and edit cards
- **Card Templates** — organisation → template file + design pack
- **Profile Customizer** — live design controls per organisation
- **Import / Export** — JSON migration between sites

---

## Notes

- Canonical redirects are disabled for singular `person_details` so custom org prefixes resolve correctly.
- Import media sideload needs the source media URLs to still be reachable at import time.
- After changing rewrite-related code or restoring a site, re-save permalinks if org URLs stop working.
