# Phase 5b — Hero slider: session instructions

> BEFORE SENDING: edit the "Brand name" line (keep one option) and make sure
> `public/assets/images/hero/necklace.png` exists (a transparent cutout).

Read docs/CONVENTIONS.md fully (it wins on conflict) and the needed parts of docs/PROJECT_BRIEF.md (per CLAUDE.md). Phases 0–4d are committed. Run the suite first to confirm the baseline (1204 passing).

Start Phase 5b: the hero, described in docs/design/hero-prompt.md, with the concept image docs/design/hero-mockup.png. The prompt was written for a single static hero without knowledge of this repo. These decisions replace it where they conflict:

## A 3-slide hero slider

- Slides, in order (images are FIXED assets committed in the repo, NOT admin uploads):
    1. ring: `public/assets/images/hero/ring.png`
    2. necklace: `public/assets/images/hero/necklace.png`
    3. bangles: `public/assets/images/hero/bangles.png`
- Shared background for all slides (it stays still while the slides change):
    - `public/assets/images/hero/silk-2k.webp` (default)
    - `silk-4k.webp` (very wide screens only)
    - `silk-mobile.webp` (portrait phones)

    Use `<picture>`/srcset with media queries, so a phone never downloads the 4K file.

- The cutouts are transparent PNGs. Produce optimised transparent WebP versions (1x + 2x, alpha kept) next to them with a small scratch GD script, and verify the alpha survived. Keep the PNG sources. Never invent paths: if necklace.png is missing, STOP and tell me.
- Composition references for slides 2 and 3: `docs/design/slide-necklace-ref.png` and `slide-bangles-ref.png`. Match their placement and mood; they are not shipped.
- Every slide uses the SAME animation: the entrance sequence for its text when it becomes active, plus the continuous float of its jewelry. The light and particles are shared on the background layer.
- Slider: reuse Bootstrap 5's carousel (already bundled, no new library), crossfade.
    - Autoplay about 7s; pause on hover and on keyboard focus.
    - A visible pause/play button (WCAG 2.2.2) and indicators styled like the mockup's line+dots, with accessible labels.
    - Swipe on touch; correct direction in RTL.
    - prefers-reduced-motion: no autoplay, no float, no particle motion.
    - Only the first slide's jewelry loads eagerly (LCP, fetchpriority=high); the others lazy-load.
    - No layout shift when slides change; slide heights are stable.
    - Without JS: the first slide shows, fully usable.
- Admin-editable TEXT per slide, in Settings (Homepage hero section, one group per slide), ar + en:
    - title, subtitle, CTA label, CTA URL (https or a relative site path), and an "enabled" switch;
    - both-languages-or-none like the other translated settings;
    - a disabled slide is skipped; with one enabled slide there are no controls and no autoplay;
    - with all disabled, slide 1 shows with its fallback text.
    - Migrate the existing hero_title / hero_subtitle / hero_cta_label / hero_cta_url values into slide 1 so nothing the owner saved is lost.
    - The images are fixed now, so remove the hero image upload from Settings; keep any old stored file untouched on disk, and say what you did with the setting.
- Fallback texts (lang files, all marked REVIEW, all added to docs/COPY_REVIEW.md):
    1. ar «حكايتكِ من الذهب تبدأ هنا» / «تفاصيل من الذهب، صُممت لترافق أجمل لحظات حياتكِ.»; en "Your Story Begins in Gold" / "Gold creations designed to become part of your most beautiful moments."
    2. ar «تفاصيل تليق بأناقتكِ» / «قطع مرصّعة بعناية لتكمل إطلالتكِ في كل مناسبة.»; en "Details Worthy of Your Elegance" / "Pieces set with care to complete your look on every occasion."
    3. ar «ذهب يحكي عنكِ» / «أساور تعبّر عن ذوقكِ في كل لحظة.»; en "Gold That Speaks of You" / "Bangles that express your taste in every moment."
    - CTA label fallback for all slides: ar «اكتشفي مجموعتنا» / en "Discover Our Collection"; CTA URL fallback: the catalog.
- Eyebrow: the Latin brand line, as in the mockup.
- Alt text: each jewelry image has a translated alt (e.g. "Gold engagement ring with a diamond"); the background is decorative.

## Other rules for the hero

- Stack: no Tailwind. Use front.css / front.js, Bootstrap, and jQuery. No new libraries.
- Colours: the existing tokens only; at most ONE new deep-background token, justified.
- Composition (logical, not hardcoded): text at the inline-start, jewelry at the inline-end (Arabic = text right, jewelry left; English = text left, jewelry right). On phones, stack them so the text and CTA stay readable and the jewelry never covers them.
- Motion:
    - float, moving light, and particles are NEW effects: add them as tokens and documented patterns in CONVENTIONS "Motion";
    - transform/opacity only, html.js scoping with the failsafe;
    - at most 12 particles on desktop, none or very few on phones, no JS animation loops (the carousel's own timer is fine);
    - the text entrance reuses the data-enter pattern, re-triggered per active slide.
- Header: on the homepage only, transparent over the hero, solid with the existing .is-scrolled state; readable over the silk. Other pages unchanged.
- Add the scroll cue from the mockup.
- The GA cookie notice (4d) must not cover the hero CTA or the slider controls on phones.
- Keep everything else intact: tests, section order, empty-settings behaviour.

## Rules

- CLAUDE.md subagent rule (subagents run only tests and read-only commands).
- Edit/Write only; php -S with the storage path set in the router; real storage untouched; never kill processes by image name.
- MySQL only by the lead, after a mysqldump backup saved OUTSIDE the Temp folder (D:\backups\yaqot\).
- No commit without my approval.

## Verification

- full suite, Pint, and view:cache;
- tests: settings per slide (validation, both-languages rule, disabled slides, the migration of the old hero values), rendered slide count, image paths exist (AssetPathsTest), and no 4K request on phones (check the `<picture>` markup);
- screenshots of the home page in both languages at 1440/768/390/360, for each slide;
- slowed motion frames of a slide change, the entrance, and the float;
- reduced-motion and no-JS screenshots;
- no overflow, no console errors.

Report, then STOP.
