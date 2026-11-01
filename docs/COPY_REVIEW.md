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
| 39 | `product::show.price_note` | تتبع أسعارنا سعر الذهب اليومي. اسألونا عن هذه القطعة. | Our prices follow the daily gold price. Ask us about this piece. | **Needs the owner's sign-off.** A business claim about pricing (Phase 4b, shown under the WhatsApp button). States no price, discount or availability. Wrong if the shop also sells at fixed prices. |
| 40 | `page::about.title` | من نحن | About us | Phase 4b, About page. 40–44. |
| 41 | `page::about.empty_title` | قصتنا قادمة قريبًا | Our story is coming soon | Shown while the "about" setting is empty. |
| 42 | `page::about.empty_text` | نجهّز هذه الصفحة حاليًا. يُرجى العودة قريبًا. | We are preparing this page. Please check back soon. | Same. |
| 43 | `page::about.cta_title` | اسألنا عن أي قطعة | Ask us about any piece | |
| 44 | `page::about.cta_text` | راسلنا أو اتصل بنا وسنساعدك في معرفة التوفّر والتفاصيل. | Send us a message or call, and we will help you with availability and details. | |
| 45 | `page::contact.title` | تواصل معنا | Contact us | Phase 4b, Contact page. 45–54. |
| 46 | `page::contact.lead` | تواصل معنا مباشرة، أو زر أحد فروعنا. | Reach us directly, or visit one of our branches. | |
| 47 | `page::contact.direct_title` | تواصل معنا | Get in touch | |
| 48 | `page::contact.branches_title` | فروعنا | Our branches | |
| 49 | `page::contact.address` | العنوان | Address | Visually hidden label. |
| 50 | `page::contact.working_hours` | مواعيد العمل | Working hours | |
| 51 | `page::contact.map` | عرض على الخريطة | View on map | |
| 52 | `page::contact.follow` | تابعونا | Follow us | Screen-reader label of the social links. |
| 53 | `page::contact.empty_title` | بيانات التواصل قادمة قريبًا | Contact details are coming soon | Shown while no contact detail or branch is configured. |
| 54 | `page::contact.empty_text` | نعمل على إضافة بيانات التواصل. يُرجى العودة قريبًا. | We are adding our contact details. Please check back soon. | Same. |
| 55 | `page::privacy.title` | سياسة الخصوصية | Privacy policy | **The whole privacy policy (55–65) needs the owner's review before launch.** It describes only what the code does today; it states no retention periods, company name, jurisdiction or legal basis. |
| 56 | `page::privacy.lead` | توضّح هذه الصفحة بكلمات بسيطة كيف يتعامل هذا الموقع مع المعلومات عند زيارتك له. | This page explains, in plain words, how this website handles information when you visit it. | |
| 57 | `page::privacy.sections.no_accounts` | لا تحتاج إلى حساب لاستخدام هذا الموقع، وليس فيه نماذج تطلب اسمك أو رقم هاتفك أو بريدك الإلكتروني أو عنوان منزلك. ولا يستقبل الموقع طلبات شراء أو مدفوعات. | You do not need an account to use this website, and it has no forms that ask for your name, phone number, email address or home address. It does not take orders or payments. | Heading «لا حسابات ولا نماذج» / "No accounts and no forms". |
| 58 | `page::privacy.sections.search` | عندما تبحث في المجوهرات أو تصفّيها، يُرسَل ما تختاره إلى هذا الموقع فقط لعرض القطع المطابقة، ولا يحفظه الموقع. تظهر كلمات البحث في عنوان الصفحة، وقد يسجّله خادم الويب في سجلاته. | When you search or filter the jewelry, what you choose is sent to this website only to show you matching pieces, and the website does not save it. Your search words do appear in the page address, which the web server may record in its logs. | Heading «البحث والتصفية» / "Search and filters". |
| 59 | `page::privacy.sections.cookies` | يستخدم هذا الموقع فقط ملفات تعريف الارتباط الصغيرة التي يحتاجها برنامجه ليعمل ويظل آمنًا، مثل ملف الجلسة. لا نستخدم هذه الملفات للإعلانات أو لتتبّعك. | This website uses only the small cookies its software needs to work and to keep it secure, such as a session cookie. We do not use cookies for advertising or to track you. | Heading «ملفات تعريف الارتباط» / "Cookies". Laravel's session and CSRF cookies. |
| 60 | `page::privacy.sections.third_party` | لا يستخدم هذا الموقع أدوات تحليل أو إعلان. تُقدَّم نصوصه البرمجية وخطوطه وصوره من الموقع نفسه، وليس من خوادم شركات أخرى. | This website does not use analytics or advertising tools. Its scripts, fonts and images are served from this website itself, not from other companies’ servers. | Heading «لا أدوات تحليل ولا خدمات خارجية» / "No analytics or outside services". **Re-check if image storage ever moves to a CDN or another host.** |
| 61 | `page::privacy.sections.contact_links` | روابط واتساب وأرقام الهاتف والبريد الإلكتروني تفتح التطبيق على جهازك أنت فقط. أي رسالة ترسلها أو مكالمة تجريها تتولاها تلك الخدمة أو مزوّد الهاتف وفق قواعده، ولا يجمعها هذا الموقع. | Links to WhatsApp, phone numbers and email addresses only open the app on your own device. A message you send or a call you make is handled by that service or your phone provider under its own rules, and this website does not collect it. | Heading «روابط واتساب والهاتف والبريد» / "WhatsApp, phone and email links". |
| 62 | `page::privacy.sections.external_links` | روابط صفحاتنا على وسائل التواصل الاجتماعي وروابط الخرائط وواتساب تفتح مواقع أو تطبيقات أخرى، ولها ممارسات الخصوصية الخاصة بها. | Links to our social media pages, to maps and to WhatsApp open other sites or apps, which have their own privacy practices. | Heading «روابط لمواقع أخرى» / "Links to other sites". Widened at the 4b review. |
| 63 | `page::privacy.sections.logs` | مثل معظم المواقع، قد يحتفظ خادم الويب الذي يستضيف هذا الموقع بسجلات فنية، مثل وقت الزيارة والصفحة المطلوبة وعنوان IP للجهاز. ويتوقف ما يُسجَّل على خدمة الاستضافة. | Like most websites, the web server that hosts this site may keep technical logs, such as the time of a visit, the page requested and the device’s IP address. What is recorded depends on the hosting service. | Heading «سجلات الخادم» / "Server logs". Depends on the host (still unknown). |
| 64 | `page::privacy.questions_title` | أسئلة | Questions | Shown only when the email setting is filled in. |
| 65 | `page::privacy.questions_text` | إذا كان لديك سؤال عن هذه الصفحة، راسلنا على | If you have a question about this page, write to us at | Followed by the email link. |

The product page's other new strings (`product::show.*`: attribute labels, "You may also like", gallery captions, "Contact us") are plain labels and carry no marker.

## Decided

| Key | Arabic | English | Decision |
|---|---|---|---|
| `product::product.gold_colors.multi` | متعدد الألوان | Multi-tone | Changed by the owner on 2026-10-28 (was «أكثر من لون»). |
