<?php

return [

    'title' => 'الفروع',
    'create' => 'إضافة فرع',
    'edit' => 'تعديل الفرع',
    'search_placeholder' => 'ابحث في الفروع بالاسم أو رقم الهاتف…',
    'empty' => 'لا توجد فروع بعد. أضف أول فرع ليعرف الزوار أين يجدونكم.',
    'table_caption' => 'الفروع مع أرقام الهاتف والحالة',

    'columns' => [
        'phone' => 'الهاتف',
    ],

    'sections' => [
        'contact' => 'التواصل والموقع',
    ],

    'fields' => [
        'name' => 'الاسم',
        'address' => 'العنوان',
        'working_hours' => 'مواعيد العمل',
        // REVIEW: generic example format only, not real opening hours.
        'working_hours_help' => 'اختياري. نص عادي، مثل: «السبت–الخميس: 00:00–00:00».',
        'phone' => 'الهاتف',
        'phone_help' => 'رقم أرضي أو محمول مصري، أو خط ساخن من 5 أرقام (19XXX). يُحفظ بالصيغة المحلية، مثل 0XXXXXXXXXX.',
        'whatsapp' => 'رقم واتساب',
        'whatsapp_help' => 'اختياري. رقم محمول مصري؛ يُحفظ بالصيغة الدولية التي يستخدمها واتساب.',
        'map_url' => 'رابط الخريطة',
        'map_url_help' => 'اختياري. رابط مشاركة من خرائط Google (https).',
    ],

];
