<?php

return [

    'title' => 'لوحة التحكم',

    'nav' => [
        'main' => 'قائمة لوحة التحكم',
        'toggle' => 'إظهار/إخفاء القائمة',
        'dashboard' => 'الرئيسية',
        'products' => 'المنتجات',
        'categories' => 'الأقسام',
        'collections' => 'المجموعات',
        'brands' => 'العلامات التجارية',
        'branches' => 'الفروع',
        'settings' => 'الإعدادات',
    ],

    'navbar' => [
        'breadcrumb' => 'مسار التنقل',
        'view_site' => 'عرض الموقع',
        'logout' => 'تسجيل الخروج',
    ],

    'language' => [
        'switch_to' => 'التبديل إلى :language',
    ],

    'actions' => [
        'create' => 'إضافة جديد',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'search' => 'بحث',
        'clear' => 'مسح',
        'actions' => 'الإجراءات',
        'edit_item' => 'تعديل «:name»',
        'delete_item' => 'حذف «:name»',
    ],

    'listing' => [
        'search_placeholder' => 'ابحث بالاسم…',
        'filters' => 'البحث والتصفية',
        'any' => 'الكل',
        'missing_translation' => '(لا يوجد اسم بهذه اللغة)',
        'empty' => 'لا توجد عناصر بعد.',
        'empty_filtered' => 'لا توجد نتائج تطابق البحث أو التصفية.',
        'sort_by' => 'ترتيب حسب :column',
    ],

    'status' => [
        'label' => 'الحالة',
        'active' => 'مفعّل',
        'inactive' => 'غير مفعّل',
        'toggle' => ':field: :name',
    ],

    'form' => [
        'required' => 'مطلوب',
        'content' => 'المحتوى',
        'languages' => 'اللغات',
        'language_names' => [
            'ar' => 'العربية',
            'en' => 'الإنجليزية',
        ],
        'tab_has_errors' => '(به أخطاء)',
        'settings' => 'الظهور والترتيب',
        'image' => [
            'label' => 'الصورة',
            'hint' => 'JPG أو PNG أو WebP، بحد أقصى :size ميجابايت، ومن :min إلى :max بكسل لكل ضلع. تُصغَّر الصور الأكبر إلى :edge بكسل.',
            'none' => 'لا توجد صورة بعد',
            'current' => 'الصورة الحالية',
            'preview' => 'معاينة الصورة المختارة (لم تُحفظ بعد)',
            'remove' => 'حذف الصورة الحالية',
        ],
        'sort_order' => 'ترتيب العرض',
        'sort_order_help' => 'الأرقام الأصغر تظهر أولًا.',
        'is_active' => 'مفعّل',
        'is_active_help' => 'العناصر غير المفعّلة لا تظهر في الموقع.',
    ],

    'columns' => [
        'name' => 'الاسم',
        'image' => 'الصورة',
        'products' => 'المنتجات',
        'sort_order' => 'الترتيب',
        'updated_at' => 'آخر تحديث',
    ],

    'modals' => [
        'delete_title' => 'تأكيد الحذف',
        'delete_body' => 'هل أنت متأكد من حذف «:name»؟ لا يمكن التراجع عن ذلك.',
        'cancel' => 'إلغاء',
        'confirm_delete' => 'حذف',
    ],

    'messages' => [
        'saved' => 'تم الحفظ بنجاح.',
        'deleted' => 'تم الحذف بنجاح.',
        'validation_failed' => 'يرجى مراجعة الحقول المظللة.',
        'session_expired' => 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.',
        'error' => 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.',
        'saving' => 'جارٍ الحفظ…',
        'close' => 'إغلاق',
        'status_changed' => 'تم تغيير الحالة إلى «:status».',
        'in_use' => 'لا يمكن حذف هذا العنصر لأنه مرتبط بمنتجات (:products). يمكنك إلغاء تفعيله بدلًا من ذلك.',
        'products_count' => '{1} منتج واحد|{2} منتجان|[3,10] :count منتجات|[11,*] :count منتجًا',
        'in_trash_joiner' => '',
        'in_trash_count' => '{1} ، منها منتج واحد في سلة المحذوفات|{2} ، منها منتجان في سلة المحذوفات|[3,10] ، منها :count منتجات في سلة المحذوفات|[11,*] ، منها :count منتجًا في سلة المحذوفات',
    ],

    'validation' => [
        'egyptian_mobile' => 'يجب أن يكون :attribute رقم موبايل مصريًا، مثل 01012345678.',
    ],

];
