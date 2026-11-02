# Task: Build a Cinematic Luxury Hero with Floating Jewelry for Yaqot

> Owner's original design brief. It was written for a single static hero, without
> knowledge of this repository. The Phase 5b instructions in
> `docs/design/hero-session.md` replace it wherever they conflict (3-slide slider,
> fixed images, admin-editable text, existing tokens, logical composition rule).

Act as a senior frontend engineer and UI/UX designer specializing in premium jewelry and luxury brand websites.

Implement a production-ready, cinematic hero section for **Yaqot Jewelry (ياقوت)** inside the existing Laravel project.

The goal is to transform the current homepage hero into a premium, immersive experience inspired by high-end jewelry campaigns, combining cinematic lighting, floating jewelry, elegant typography, and subtle motion.

## 1. Inspect the Existing Project First

Before writing code:

- Inspect the existing Laravel version, frontend stack, Blade templates, layouts, components, routes, CSS, JavaScript, and localization implementation.
- Locate the current homepage hero and identify its dependencies.
- Inspect the existing brand logo, product images, fonts, and design tokens.
- Determine how Arabic/English switching and RTL/LTR layouts currently work.
- Reuse existing dependencies and components wherever practical.
- Do not assume any file names, component names, routes, or image paths exist before inspecting the repository.

Preserve existing functionality, user changes, and the overall website structure. Do not rewrite the homepage or refactor unrelated components.

## 2. Visual Direction

Create a premium cinematic hero with the following composition:

- Deep black background: `#080808` or a similar near-black shade.
- Dark charcoal surfaces: `#151515`.
- Warm metallic gold accents: `#D4AF66`.
- Soft ivory text: `#F2EBDD`.
- A large, photorealistic gold engagement ring with a diamond, positioned prominently.
- Warm golden light rays behind the ring.
- Subtle floating golden particles.
- A flowing, abstract golden light ribbon or silk-like highlight behind the jewelry.
- Elegant typography and generous negative space.
- Thin gold borders and restrained decorative details.

The result must feel like a high-end jewelry advertising campaign, not a generic ecommerce template.

Avoid excessive gradients, neon colors, glassmorphism, exaggerated glow effects, and constant distracting motion.

## 3. Hero Layout

Build a responsive hero with two primary areas.

### Jewelry Visual

- Display a large, premium jewelry image as the visual centerpiece.
- Use a suitable transparent WebP/PNG image.
- Use `object-fit`, responsive sizing, and appropriate positioning to preserve the jewelry's visual quality.
- The image should blend naturally into the dark background.

### Text Content

Arabic content:

- Eyebrow: "YAQOT JEWELRY"
- Main headline: "حكايتكِ من الذهب تبدأ هنا"
- Description: "تفاصيل من الذهب، صُممت لترافق أجمل لحظات حياتكِ."
- Primary CTA: "اكتشفي مجموعتنا"

English content:

- Eyebrow: "YAQOT JEWELRY"
- Main headline: "Your Story Begins in Gold"
- Description: "Gold creations designed to become part of your most beautiful moments."
- Primary CTA: "Discover Our Collection"

The CTA must navigate to the existing collections or catalog route. Use the actual route discovered during repository inspection.

The headline must remain visually balanced and readable without awkward line breaks on desktop or mobile.

## 4. Animation Sequence

Implement the animations with CSS and the existing JavaScript stack wherever possible.

### Initial Page Entrance

When the hero first becomes visible:

1. Fade in the background and lighting.
2. Reveal the jewelry image with a gentle opacity transition and subtle scale animation.
3. Animate the eyebrow text into view.
4. Reveal the headline with a smooth upward motion.
5. Fade in the description.
6. Reveal the CTA last.

Use staggered timing to create a refined cinematic entrance.

Suggested timing:

- Background: 700–1000 ms.
- Jewelry reveal: 900–1400 ms.
- Text sequence: 500–800 ms per stage.
- Use smooth easing such as `cubic-bezier(0.22, 1, 0.36, 1)`.

Do not replay the full entrance animation on every render or unnecessarily when switching unrelated UI state.

### Floating Jewelry

- Gentle vertical movement of approximately 8–14 pixels.
- A very slight rotation, preferably no more than 1–2 degrees.
- A slow cycle of approximately 6–9 seconds.
- Smooth, continuous motion without sudden jumps.

Keep the headline, description, and CTA stable while the jewelry floats.

Do not use a fake 3D rotation on a flat image that creates an unnatural appearance.

### Golden Light

- Use a CSS radial gradient, pseudo-element, or an existing lightweight animation technique.
- Move the light slowly across the background.
- Keep opacity low enough that the jewelry remains the main focus.
- Avoid flashing, harsh beams, and excessive bloom.

### Floating Particles

- Use lightweight CSS elements.
- Vary their size, opacity, position, and duration.
- Keep them behind the main content.
- Avoid large particle counts or expensive continuous JavaScript calculations.
- Disable or simplify particles on small screens if needed for performance.

### CTA Interaction

- A refined gold background.
- Dark, readable text.
- A subtle hover transition.
- A slight upward movement or gentle brightness change on hover.
- Visible keyboard focus.
- A comfortable touch target on mobile.

Do not add exaggerated bouncing or pulsing effects.

## 5. Arabic and English Direction

- Integrate the hero with the existing language-switching system.
- Use natural Arabic typography and appropriate line height.
- Ensure punctuation, alignment, and button icons display correctly.
- Direction must update dynamically when the language changes.
- Do not hardcode separate Arabic and English layouts.
- Use logical CSS properties such as `margin-inline`, `padding-inline`, and `inset-inline`.
- Preserve the current URL and routing behavior when switching languages.

## 6. Responsive Behavior

### Desktop

- A spacious two-column composition.
- The jewelry large and visually dominant.
- A large headline with controlled maximum width.
- No unnecessary vertical whitespace.

### Tablet

- Reduce headline size and visual scale.
- Adjust the column proportions and spacing.
- Keep the main CTA immediately visible.

### Mobile

- Stack the text and jewelry visual into a deliberate composition.
- Keep the headline readable and avoid excessive wrapping.
- Reduce or disable particles and expensive effects.
- Prevent horizontal overflow.
- Keep the CTA accessible without forcing users to scroll excessively.
- Ensure the image does not obscure the main text.

Use responsive typography and spacing rather than fixed desktop dimensions.

## 7. Hero Controls and Next Section

Include a subtle scroll cue at the bottom of the hero, pointing toward the next section.

The following section should begin naturally after the hero, with a refined transition and no excessive blank space.

## 8. Implementation Requirements

- Follow the existing Laravel and frontend architecture.
- Do not introduce a new framework just to animate the hero.
- Do not install GSAP, Three.js, or other animation libraries.
- Use CSS animations for floating motion, opacity, transforms, and lighting where practical.
- Avoid unnecessary DOM manipulation.
- Avoid layout shifts during image loading.
- Provide explicit image dimensions or aspect ratios where appropriate.
- Optimize image delivery and use responsive assets.
- Do not modify unrelated pages.

## 9. Accessibility and Performance

- Respect `prefers-reduced-motion: reduce`.
- Keep text contrast accessible.
- Ensure the CTA is keyboard accessible.
- Provide appropriate alternative text for meaningful jewelry images.
- Mark purely decorative lighting and particle layers as hidden from assistive technologies.
- Prevent decorative layers from blocking pointer interactions.
- Ensure the page remains usable if animation CSS or JavaScript fails.
- Optimize the hero's main image and avoid unnecessary loading of below-the-fold assets.

## 10. Testing and Verification

1. Test the homepage in Arabic RTL mode.
2. Test the homepage in English LTR mode.
3. Verify the language switcher updates the hero layout and content.
4. Verify the CTA navigates to the correct existing route.
5. Test desktop, tablet, and mobile layouts.
6. Check for horizontal overflow and awkward text wrapping.
7. Verify reduced-motion behavior.
8. Confirm the hero images load successfully.
9. Run the relevant existing tests and formatting checks.
10. Inspect the final diff for unrelated changes or regressions.

Do not claim tests passed unless they were actually executed.

## 11. Acceptance Criteria

- The hero has a premium black-and-gold cinematic appearance.
- The jewelry is the primary visual focus.
- The jewelry floats subtly and naturally.
- Golden lighting and particles provide atmosphere without distraction.
- Text and CTA appear with a polished staggered entrance.
- Arabic and English layouts mirror correctly.
- The hero works on mobile, tablet, and desktop.
- Reduced-motion preferences are respected.
- Existing navigation, localization, and catalog functionality remain intact.
- No fake asset paths, contact details, or nonfunctional links are introduced.
