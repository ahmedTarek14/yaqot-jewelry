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
| 1 | `home::home.hero.eyebrow` | مجوهرات ذهب | Gold jewelry | Small line above the hero title. Since Phase 4a the hero title, subtitle, image and button come from Settings; the title falls back to the brand name and the subtitle is hidden while empty (the old `hero.title` / `hero.subtitle` placeholders were removed). |
| 4 | `home::home.hero.cta` | تصفح المجوهرات | Browse jewelry | Hero button label while the "button label" setting is empty. |
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
| 28 | `front.whatsapp.general_message` | مرحبًا ياقوت، أود الاستفسار عن مجوهراتكم. | Hello Yaqot, I would like to ask about your jewelry. | Added in 4a. The text a visitor's WhatsApp opens with from the homepage and footer; they can edit it before sending. |
| 29 | `front.whatsapp.product_message` | مرحبًا ياقوت، أود الاستفسار عن هذه القطعة: (الاسم) (كود …) (الرابط) هل هي متوفرة؟ | Hello Yaqot, I am interested in this piece: (name) (code …) (link) Is it available? | Added in 4a, used from the product page (4b). Arabic is worded neutrally (no «مهتمة/مهتم»). |
| 30 | `home::home.sections.categories` | تسوّق حسب القسم | Shop by category | Homepage section headings, 30–36. |
| 31 | `home::home.sections.featured` | قطع مختارة | Featured pieces | |
| 32 | `home::home.sections.collections` | المجموعات | Collections | |
| 33 | `home::home.sections.brands` | الماركات المتوفرة لدينا | Brands we carry | |
| 34 | `home::home.sections.latest` | وصل حديثًا | New arrivals | |
| 35 | `home::home.sections.contact` | اسألنا عن أي قطعة | Ask us about any piece | |
| 36 | `home::home.sections.contact_text` | راسلنا أو اتصل بنا، وسنساعدك في معرفة التوفر والتفاصيل. | Send us a message or call, and we will help you with availability and details. | Promises only a reply about availability, nothing else. |
| 37 | `setting::setting.sections.homepage.description` | ترتيب أقسام الصفحة الرئيسية. | How the homepage sections are arranged. | Added at the 4a review, with the "prominent collection" setting. |
| 38 | `setting::setting.fields.prominent_collection_help` | تظهر أولًا وبحجم كبير بين مجموعات الصفحة الرئيسية، إذا كانت مفعّلة للظهور فيها وبها قطع منشورة. | Shown first and large among the homepage collections, when it is set to appear on the homepage and has published pieces. | Same as above. |

## Decided

| Key | Arabic | English | Decision |
|---|---|---|---|
| `product::product.gold_colors.multi` | متعدد الألوان | Multi-tone | Changed by the owner on 2026-10-28 (was «أكثر من لون»). |

## Still to come

Phase 4b will add copy that must be approved too, and will list it here: the
gold-price note on the product page and the privacy policy.
