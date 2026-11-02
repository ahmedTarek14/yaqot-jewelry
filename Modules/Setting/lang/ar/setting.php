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
        'homepage' => [
            'title' => 'الصفحة الرئيسية',
            'description' => 'ترتيب أقسام الصفحة الرئيسية.',
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
        'analytics' => [
            'title' => 'Google Analytics',
            // REVIEW: wording of the analytics section.
            'description' => 'إحصاءات اختيارية عن الزوّار من جوجل.',
        ],
    ],

    'validation' => [
        // REVIEW: error shown for a malformed Google Analytics measurement ID.
        'ga_measurement_id' => 'أدخل معرّف قياس صحيحًا مثل G-ABC123XYZ9، أو اترك الحقل فارغًا.',
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
        'prominent_collection' => 'المجموعة المميزة',
        'prominent_collection_none' => 'لا شيء',
        'prominent_collection_help' => 'تظهر أولًا وبحجم كبير بين مجموعات الصفحة الرئيسية، إذا كانت مفعّلة للظهور فيها وبها قطع منشورة.',
        'ga_measurement_id' => 'معرّف القياس',
        // REVIEW: help text of the Google Analytics field.
        'ga_measurement_id_help' => 'يبدو هكذا: G-XXXXXXXXXX. اتركه فارغًا لإيقاف التتبّع: لا يُحمَّل شيء من جوجل ولا يرى الزوّار إشعار ملفات تعريف الارتباط. وعند تعبئته يرى الزوّار إشعارًا بملفات تعريف الارتباط، ولا يُحمَّل Google Analytics إلا بعد موافقتهم.',
        'about_content' => 'نص «من نحن»',
        'about_content_help' => 'نص عادي؛ السطر الفارغ يبدأ فقرة جديدة.',
        'footer_text' => 'نص التذييل',
        'meta_description' => 'وصف الموقع لمحركات البحث',
        'meta_description_help' => 'حتى 300 حرف؛ يظهر نحو 150 حرفًا في نتائج البحث.',
    ],

];
