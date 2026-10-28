# Copy review

Every string marked `// REVIEW:` in the lang files, in one place for the owner's
review. Keys are written as they are used in code (`module::file.key`).
To change a string, edit it in both lang files (`Modules/{Module}/lang/{ar,en}/`);
once a string is approved, remove its `// REVIEW:` marker.

_Generated at the end of Phase 3b, 2026-10-28. Placeholders such as `:name`
are filled in by the app; `00:00` and `0XXXXXXXXXX` are deliberately generic
examples, not real data._

## To review

| # | Key | Arabic | English | Note |
|---|---|---|---|---|
| 1 | `home::home.hero.eyebrow` | مجوهرات ذهب | Gold jewelry | Placeholder hero until Phase 4 reads the hero from Settings. |
| 2 | `home::home.hero.title` | ياقوت | Yaqot | Same as above. |
| 3 | `home::home.hero.subtitle` | قطع ذهب ومجوهرات راقية لكل مناسبة. | Fine gold and jewelry pieces for every occasion. | Same as above. |
| 4 | `home::home.hero.cta` | تصفح المجوهرات | Browse jewelry | Same as above. |
| 5 | `product::product.fields.sku` | الكود (SKU) | SKU | «الكود» is the everyday term in Egyptian shops; "SKU" kept beside it. |
| 6 | `product::product.fields.no_brand` | بدون ماركة (من إنتاجنا) | No brand (house piece) | Shown in the brand select for the shop's own pieces. |
| 7 | `product::product.fields.stone_type` | الفصوص | Stones | «فصوص» is the common Egyptian word for set stones. |
| 8 | `product::product.gold_colors.yellow` | ذهب أصفر | Yellow gold | Colour names as used at the counter. |
| 9 | `product::product.gold_colors.white` | ذهب أبيض | White gold | |
| 10 | `product::product.gold_colors.rose` | ذهب روز | Rose gold | Alternative: «ذهب وردي». |
| 11 | `product::product.stone_types.none` | بدون فصوص | No stones | |
| 12 | `product::product.stone_types.diamond` | ألماس | Diamond | |
| 13 | `product::product.stone_types.zircon` | زركون | Zircon | |
| 14 | `product::product.stone_types.gemstone` | أحجار كريمة | Gemstones | |
| 15 | `product::product.stone_types.pearl` | لؤلؤ | Pearl | |
| 16 | `collection::collection.empty` | لا توجد مجموعات بعد. أضف أول مجموعة لتجميع القطع حسب الطابع أو المناسبة. | No collections yet. Add the first one to group pieces by theme or occasion. | Admin empty state. |
| 17 | `collection::collection.fields.show_on_homepage_help` | تظهر في الصفحة الرئيسية ما دامت المجموعة مفعّلة أيضًا. | Featured on the homepage while the collection is also active. | |
| 18 | `collection::collection.image_help` | تظهر في بطاقة المجموعة وأعلى صفحتها. | Shown on the collection tile and page header. | Confirm once the public pages exist (Phase 4). |
| 19 | `brand::brand.empty` | لا توجد علامات تجارية بعد. أضف أول علامة لتصنيف المنتجات حسب الصانع. | No brands yet. Add the first one to label products by maker. | Admin empty state. |
| 20 | `brand::brand.fields.name_help` | اسم العلامة التجارية كما يعرفه العملاء. | The brand name as customers know it. | |
| 21 | `branch::branch.fields.working_hours_help` | اختياري. نص عادي، مثل: «السبت–الخميس: 00:00–00:00». | Optional. Plain text, for example “Saturday–Thursday: 00:00–00:00”. | Generic format only, not real opening hours. |
| 22 | `setting::setting.sections.contact.description` | تظهر في تذييل الموقع وصفحة التواصل. | Shown in the footer and on the contact page. | Says where each value appears; confirm once the public pages are built. |
| 23 | `setting::setting.sections.social.description` | روابط كاملة تبدأ بـ https لصفحات المتجر. اترك الحقل فارغًا لإخفاء أيقونته. | Full https links to the shop’s pages. Leave a field empty to hide its icon. | Same as above. |
| 24 | `setting::setting.sections.hero.description` | الشريط الكبير أعلى الصفحة الرئيسية. | The large banner at the top of the homepage. | Same as above. |
| 25 | `setting::setting.sections.about.description` | نص صفحة «من نحن». | The text of the About page. | Same as above. |
| 26 | `setting::setting.sections.footer.description` | سطر قصير يظهر أسفل كل الصفحات. | A short line shown at the bottom of every page. | Same as above. |
| 27 | `setting::setting.sections.seo.description` | الوصف الافتراضي الذي تعرضه محركات البحث للموقع. | The default description search engines show for the site. | Same as above. |

## Decided

| Key | Arabic | English | Decision |
|---|---|---|---|
| `product::product.gold_colors.multi` | متعدد الألوان | Multi-tone | Changed by the owner on 2026-10-28 (was «أكثر من لون»). |

## Still to come

Phase 4 will add copy that must be approved too, and will list it here: the
gold-price note on the product page, the WhatsApp message, and the privacy policy.
