<?php

return [

    // The hero slider (Phase 5b) reads each slide's title, subtitle and button from Settings;
    // these are the fallbacks while a setting is empty. The images are fixed files.
    'hero' => [
        // REVIEW: the Latin brand line above every slide title (kept the same in both languages).
        'eyebrow' => 'YAQOT JEWELRY',
        // REVIEW: button label while a slide's button label setting is empty.
        'cta' => 'اكتشفي مجموعتنا',
        'label' => 'مجوهرات مميزة',
        'slide_of' => 'الشريحة :current من :total',
        'show_slide' => 'عرض الشريحة :number',
        'pause' => 'إيقاف العرض مؤقتًا',
        'play' => 'تشغيل العرض',
        'scroll' => 'مرري للأسفل لعرض المجموعة',
        'slides' => [
            1 => [
                // REVIEW: slide 1 text.
                'title' => 'حكايتكِ من الذهب تبدأ هنا',
                // REVIEW:
                'subtitle' => 'تفاصيل من الذهب، صُممت لترافق أجمل لحظات حياتكِ.',
                // REVIEW: alt text of slide 1's image.
                'alt' => 'خاتم خطوبة من الذهب بفص ألماس',
            ],
            2 => [
                // REVIEW: slide 2 text.
                'title' => 'تفاصيل تليق بأناقتكِ',
                // REVIEW:
                'subtitle' => 'قطع مرصّعة بعناية لتكمل إطلالتكِ في كل مناسبة.',
                // REVIEW: alt text of slide 2's image.
                'alt' => 'عقد من الذهب مرصّع بالألماس بتعليقة على شكل كمثرى',
            ],
            3 => [
                // REVIEW: slide 3 text.
                'title' => 'ذهب يحكي عنكِ',
                // REVIEW:
                'subtitle' => 'أساور تعبّر عن ذوقكِ في كل لحظة.',
                // REVIEW: alt text of slide 3's image.
                'alt' => 'ثلاثة أساور من الذهب مرصّعة بالألماس',
            ],
        ],
    ],

    // REVIEW: section headings and the contact invitation on the homepage.
    'sections' => [
        'categories' => 'تسوّق حسب القسم',
        'featured' => 'قطع مختارة',
        'collections' => 'المجموعات',
        'brands' => 'الماركات المتوفرة لدينا',
        'latest' => 'وصل حديثًا',
        'bullion' => 'سبايك وجنيهات', // REVIEW:
        'about' => 'عن ياقوت',
        'about_more' => 'اقرأ المزيد',
        'contact' => 'اسألنا عن أي قطعة',
        'contact_text' => 'راسلنا أو اتصل بنا، وسنساعدك في معرفة التوفر والتفاصيل.',
    ],

    'dashboard' => [
        'welcome' => 'مرحبًا، :name',
        'intro' => 'من هنا تدير منتجات ياقوت وأقسامها ومحتوى الموقع.',
        'add_product' => 'إضافة منتج',
        'stats' => [
            'heading' => 'نظرة سريعة على الكتالوج',
            'products' => 'المنتجات',
            'products_link' => 'عرض كل المنتجات',
            'published' => 'منشورة',
            'published_link' => 'عرض المنتجات المنشورة',
            'drafts' => 'مسودات',
            'drafts_link' => 'عرض المسودات',
            'active_categories' => 'الأقسام المفعّلة',
            'active_categories_link' => 'عرض الأقسام المفعّلة',
        ],
        'recent' => [
            'title' => 'آخر المنتجات المعدّلة',
            'caption' => 'أحدث المنتجات التي جرى تعديلها',
            'all' => 'كل المنتجات',
            'empty' => 'لا توجد منتجات بعد. أضف أول قطعة لتبدأ الكتالوج.',
        ],
    ],

];
