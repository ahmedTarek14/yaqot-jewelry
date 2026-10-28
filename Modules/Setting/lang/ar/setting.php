<?php

return [

    'title' => 'الإعدادات',
    'intro' => 'كل الحقول اختيارية. النص الذي تكتبه بلغة يجب أن تكتبه باللغة الأخرى أيضًا، حتى لا تظهر أي صفحة بلغة غير لغتها.',

    // REVIEW: the section descriptions say where each value appears; confirm once the public pages are built.
    'sections' => [
        'contact' => [
            'title' => 'بيانات التواصل',
            'description' => 'تظهر في تذييل الموقع وصفحة التواصل.',
        ],
        'social' => [
            'title' => 'مواقع التواصل الاجتماعي',
            'description' => 'روابط كاملة تبدأ بـ https لصفحات المتجر. اترك الحقل فارغًا لإخفاء أيقونته.',
        ],
        'hero' => [
            'title' => 'واجهة الصفحة الرئيسية',
            'description' => 'الشريط الكبير أعلى الصفحة الرئيسية.',
        ],
        'about' => [
            'title' => 'من نحن',
            'description' => 'نص صفحة «من نحن».',
        ],
        'footer' => [
            'title' => 'تذييل الموقع',
            'description' => 'سطر قصير يظهر أسفل كل الصفحات.',
        ],
        'seo' => [
            'title' => 'محركات البحث',
            'description' => 'الوصف الافتراضي الذي تعرضه محركات البحث للموقع.',
        ],
    ],

    'fields' => [
        'whatsapp_number' => 'رقم واتساب',
        'whatsapp_number_help' => 'رقم محمول مصري؛ يُحفظ بالصيغة الدولية التي يستخدمها واتساب.',
        'phone' => 'الهاتف',
        'phone_help' => 'رقم أرضي أو محمول مصري، أو خط ساخن من 5 أرقام (19XXX). يُحفظ بالصيغة المحلية، مثل 0XXXXXXXXXX.',
        'email' => 'البريد الإلكتروني',
        'facebook_url' => 'رابط فيسبوك',
        'instagram_url' => 'رابط إنستجرام',
        'tiktok_url' => 'رابط تيك توك',
        'url_help' => 'رابط كامل يبدأ بـ https://',
        'hero_title' => 'العنوان',
        'hero_subtitle' => 'العنوان الفرعي',
        'hero_cta_label' => 'نص الزر',
        'hero_cta_url' => 'رابط الزر',
        'hero_cta_help' => 'يظهر الزر فقط عند كتابة نصه ورابطه معًا (https).',
        'hero_image' => 'صورة الواجهة',
        'about_content' => 'نص «من نحن»',
        'about_content_help' => 'نص عادي؛ السطر الفارغ يبدأ فقرة جديدة.',
        'footer_text' => 'نص التذييل',
        'meta_description' => 'وصف الموقع لمحركات البحث',
        'meta_description_help' => 'حتى 300 حرف؛ يظهر نحو 150 حرفًا في نتائج البحث.',
    ],

];
