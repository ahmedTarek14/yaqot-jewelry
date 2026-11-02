<?php

return [

    'title' => 'المنتجات',
    'singular' => 'المنتج',
    'create' => 'إضافة منتج',
    'edit' => 'تعديل المنتج',
    'search_placeholder' => 'ابحث بالاسم أو الكود…',
    'empty' => 'لا توجد منتجات بعد. أضف أول قطعة لبدء بناء الكتالوج.',
    'table_caption' => 'المنتجات مع الكود وحالة النشر والتمييز',

    'sections' => [
        'details' => 'التفاصيل',
        'classification' => 'الأقسام والمجموعات',
        'images' => 'الصور',
        'publishing' => 'النشر',
    ],

    'fields' => [
        'name' => 'الاسم',
        'description' => 'الوصف',
        'description_help' => 'اختياري. نص عادي؛ السطر الفارغ يبدأ فقرة جديدة.',
        // REVIEW: "الكود" is the everyday term in Egyptian shops; "SKU" is kept in English beside it.
        'sku' => 'الكود (SKU)',
        'sku_help' => 'الكود الداخلي للقطعة. حروف إنجليزية وأرقام ونقاط وشرطات فقط.',
        'brand' => 'الماركة',
        // REVIEW
        'no_brand' => 'بدون ماركة (من إنتاجنا)',
        'type' => 'النوع',
        'type_help' => 'تظهر السبائك والجنيهات في صفحة خاصة بها، وليس في كتالوج المجوهرات.', // REVIEW:
        'karat' => 'العيار',
        'gold_color' => 'لون الذهب',
        // REVIEW: "فصوص" is the common Egyptian word for set stones.
        'stone_type' => 'الفصوص',
        'jewelry_only_help' => 'للمجوهرات فقط.', // REVIEW:
        'inactive_option' => ':name (غير مفعّل)', // REVIEW:
        'not_specified' => 'غير محدد',
        'weight_grams' => 'الوزن (جرام)',
        'weight_help' => 'اختياري. حتى ثلاثة أرقام عشرية، مثل 12.375.',
        'categories' => 'الأقسام',
        'category' => 'القسم',
        'collections' => 'المجموعات',
        'collection' => 'المجموعة',
        'new_image' => 'الصورة الجديدة رقم :position',
        'removed_image' => 'الصورة المحذوفة',
        'is_published' => 'منشور',
        'is_published_help' => 'يظهر المنتج المنشور في الموقع بشرط أن تكون له صورة واحدة على الأقل.',
        'is_featured' => 'مميز',
        'is_featured_help' => 'تظهر المنتجات المميزة في الصفحة الرئيسية.',
        'view_on_site_help' => 'تفتح صفحة المنتج في تبويب جديد.',
    ],

    'images_help' => 'الصورة الأولى هي الصورة الرئيسية، وتظهر في بطاقات المنتجات ونتائج البحث.',

    'karats' => [
        18 => '18 قيراط',
        21 => '21 قيراط',
        24 => '24 قيراط',
    ],

    // Gold colours and stone types are owner data now (Modules/Option), not lang files.

    'types' => [
        'jewelry' => 'مجوهرات',
        'bar' => 'سبيكة ذهب', // REVIEW:
        'coin' => 'جنيه ذهب', // REVIEW:
    ],

    // The bullion page's type filter.
    'types_plural' => [
        'bar' => 'سبايك ذهب', // REVIEW:
        'coin' => 'جنيهات ذهب', // REVIEW:
    ],

    'featured' => [
        'on' => 'مميز',
        'off' => 'غير مميز',
    ],

    'columns' => [
        'sku' => 'الكود',
        'featured' => 'مميز',
    ],

    'filters' => [
        'status' => 'الحالة',
        'category' => 'القسم',
        'collection' => 'المجموعة',
        'brand' => 'الماركة',
        'no_brand' => 'بدون ماركة',
        'karat' => 'العيار',
        'type' => 'النوع',
        'featured' => 'التمييز',
        'featured_yes' => 'مميز',
        'featured_no' => 'غير مميز',
        'trashed' => 'سلة المحذوفات',
        'trashed_only' => 'المحذوفات فقط',
        'trashed_with' => 'مع المحذوفات',
    ],

    'messages' => [
        'trashed' => 'نُقل المنتج إلى سلة المحذوفات. يمكنك استعادته من فلتر سلة المحذوفات.',
        'trash_confirm' => 'نقل «:name» إلى سلة المحذوفات؟ سيختفي من الموقع، ويمكنك استعادته لاحقًا من فلتر سلة المحذوفات.',
        'force_confirm' => 'حذف «:name» نهائيًا؟ سيُحذف المنتج وصوره نهائيًا، ولا يمكن التراجع عن ذلك.',
        'deleted_permanently' => 'تم حذف المنتج وصوره نهائيًا.',
    ],

    'validation' => [
        'jewelry_only' => 'لون الذهب والفصوص للمجوهرات فقط. امسحهما، أو اجعل النوع «مجوهرات».', // REVIEW:
        'sku_format' => 'الكود يقبل الحروف الإنجليزية والأرقام والنقطة (.) والشرطة (-) والشرطة السفلية (_) فقط.',
        'too_many_images' => 'لا يمكن أن يزيد عدد صور المنتج على :max، وبهذا يصبح العدد :count. احذف بعض الصور أولًا.',
        'invalid_gallery' => 'تعذّرت قراءة ترتيب الصور. أعد تحميل الصفحة وحاول مرة أخرى.',
    ],

];
