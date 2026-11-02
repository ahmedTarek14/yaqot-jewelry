<?php

return [

    // The cookie notice is shown on public pages only, and only when a Google Analytics id is set in Settings.
    'consent' => [
        // REVIEW: accessible name of the cookie notice.
        'label' => 'إشعار ملفات تعريف الارتباط',
        // REVIEW: heading of the cookie notice.
        'title' => 'ملفات تعريف الارتباط والإحصاءات',
        // REVIEW: text of the cookie notice.
        'text' => 'بموافقتك نستخدم خدمة Google Analytics لعدّ الزيارات ومعرفة الصفحات المفيدة. ولا يضع ملفات تعريف ارتباط على جهازك إلا إذا وافقت.',
        // REVIEW: link to the privacy policy inside the notice.
        'more' => 'اقرأ سياسة الخصوصية',
        // REVIEW: button label.
        'accept' => 'موافق',
        // REVIEW: button label.
        'decline' => 'رفض',
    ],

    'insights' => [
        'title' => 'نظرة على الزيارات',
        'range_label' => 'الفترة الزمنية',
        // REVIEW: "7 يوم" / "30 يوم" is colloquial; the number agreement differs for 7 and 30.
        'last_days' => 'آخر :days يوم',
        'intro' => 'إجماليات يومية مجهولة الهوية لكل منتج: عدد مرات فتح صفحته وعدد مرات الضغط على زر واتساب. لا تُحفظ أي بيانات عن الزائرين.',
        'most_viewed' => 'الأكثر مشاهدة',
        'most_asked' => 'الأكثر استفسارًا عبر واتساب',
        'unit_views' => 'مشاهدة',
        'unit_clicks' => 'نقرة على واتساب',
        'empty' => 'لا توجد بيانات لهذه الفترة بعد.',
        'chart_title' => 'الإجماليات اليومية لآخر 30 يومًا',
        'chart_desc' => 'رسم بياني خطي لمشاهدات الصفحات والنقرات على واتساب في كل يوم خلال آخر 30 يومًا، لجميع المنتجات معًا. يبدأ المحور الرأسي من 0 إلى :max.',
        'chart_empty' => 'لا يوجد نشاط خلال آخر 30 يومًا.',
        'legend' => 'دليل الرسم البياني',
        'series_views' => 'مشاهدات الصفحات (خط متصل)',
        'series_clicks' => 'نقرات واتساب (خط متقطع)',
        'show_table' => 'عرض الأرقام في جدول',
        'date' => 'التاريخ',
        'retention' => 'تُحفظ العدّادات اليومية :months شهرًا، وتُحذف الأقدم منها.',
        'prune' => [
            'button' => 'حذف العدّادات القديمة',
            'title' => 'حذف العدّادات القديمة؟',
            'body' => 'ستُحذف العدّادات الأقدم من :months شهرًا، ولن تتأثر العدّادات الأحدث. لا يمكن التراجع عن ذلك.',
            'confirm' => 'حذف',
            'done' => 'تم حذف :count من العدّادات القديمة.',
        ],
    ],

];
