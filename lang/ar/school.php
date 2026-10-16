<?php

return [
    'roles' => [
        'owner' => 'مدير النظام', 'principal' => 'مديرة المدرسة', 'vice_principal' => 'الوكيلة', 'student_affairs' => 'شؤون الطالبات',
        'health_guide' => 'الموجه الصحي', 'counselor' => 'الموجهة الطلابية', 'guardian' => 'ولي الأمر',
        'support' => 'الدعم الفني', 'translator' => 'مترجم', 'content_reviewer' => 'مراجع المحتوى',
    ],
    'role_descriptions' => [
        'principal' => 'الإشراف العام ومتابعة التقارير والإحصائيات',
        'vice_principal' => 'متابعة الغياب والتواصل مع أولياء الأمور',
        'student_affairs' => 'تسجيل الغياب والتأخر وتوثيق الإجراءات',
        'health_guide' => 'متابعة الحالات الصحية والتوعية الصحية',
        'counselor' => 'متابعة الأسباب والإرشاد ووضع خطط إرشادية',
        'guardian' => 'تسجيل الغياب وإرسال العذر والتواصل مع المدرسة',
    ],
    'status' => ['present' => 'حاضرة', 'absent' => 'غائبة', 'late' => 'متأخرة', 'excused' => 'غياب بعذر'],
    'excuse_status' => ['pending' => 'قيد المراجعة', 'approved' => 'مقبول', 'rejected' => 'مرفوض'],
    'excuse_type' => ['medical' => 'طبي', 'family' => 'ظرف عائلي', 'travel' => 'سفر', 'official' => 'مهمة رسمية', 'other' => 'أخرى'],
    'relation' => ['father' => 'الأب', 'mother' => 'الأم', 'brother' => 'الأخ', 'sister' => 'الأخت', 'grandparent' => 'الجد / الجدة', 'uncle' => 'العم / الخال', 'aunt' => 'العمة / الخالة', 'other' => 'أخرى'],
    'stage' => ['kindergarten' => 'رياض الأطفال', 'primary' => 'الابتدائية', 'intermediate' => 'المتوسطة', 'secondary' => 'الثانوية'],
    'note_category' => ['general' => 'عامة', 'health' => 'صحية', 'counseling' => 'إرشادية', 'behavior' => 'سلوكية', 'attendance' => 'مواظبة'],
    'channel' => ['whatsapp' => 'واتساب', 'call' => 'اتصال', 'email' => 'بريد إلكتروني'],
    'activity' => [
        'verbs' => [
            'created' => 'إضافة', 'updated' => 'تعديل', 'deleted' => 'حذف', 'saved' => 'حفظ', 'reset' => 'استعادة الافتراضي',
            'class_saved' => 'حفظ تحضير فصل', 'imported' => 'استيراد', 'exported' => 'تصدير', 'settings_saved' => 'حفظ الإعدادات',
            'password_link_sent' => 'إرسال رابط كلمة المرور', 'link_requested' => 'طلب ربط', 'link_approved' => 'اعتماد ربط', 'link_rejected' => 'رفض ربط',
            'assigned' => 'تعيين', 'roles_updated' => 'تحديث الأدوار', 'unlocked' => 'فك قفل الحساب', 'account_locked' => 'قفل الحساب',
            'enabled' => 'تفعيل', 'removed' => 'إزالة', 'published' => 'نشر', 'recovery_code_used' => 'استخدام رمز استرداد',
            'recovery_codes_regenerated' => 'إعادة توليد رموز الاسترداد',
        ],
        'entities' => [
            'school' => 'مدرسة', 'grade' => 'صف', 'classroom' => 'فصل', 'student' => 'طالبة', 'students' => 'الطالبات',
            'attendance_record' => 'سجل حضور', 'attendance' => 'الحضور', 'excuse' => 'عذر', 'student_note' => 'ملاحظة',
            'guardian_contact' => 'تواصل مع ولي الأمر', 'guardian' => 'ولي الأمر', 'employees' => 'موظفة', 'reports' => 'تقرير',
            'owner' => 'مدير النظام', 'members' => 'عضو', 'roles' => 'دور', 'auth' => 'تسجيل الدخول', 'mfa' => 'المصادقة الثنائية',
            'integrations' => 'التكاملات', 'language.visibility' => 'ظهور اللغة', 'translations' => 'الترجمة', 'support' => 'الدعم',
            'pages' => 'صفحة', 'site-notices' => 'إشعار الموقع', 'email-templates' => 'قالب بريد', 'user' => 'مستخدم',
            'settings' => 'إعدادات الأمان', 'appearance' => 'المظهر', 'maintenance' => 'الصيانة', 'branding' => 'الهوية',
            'homepage' => 'الصفحة الرئيسية', 'guardians' => 'تسجيل أولياء الأمور', 'school-notifications' => 'إشعارات المدرسة',
            'staff-alerts' => 'تنبيهات الفريق', 'storage' => 'التخزين', 'icons' => 'الأيقونات', 'cookies' => 'ملفات الارتباط',
            'social' => 'روابط التواصل', 'styling' => 'الألوان', 'fonts' => 'الخطوط',
        ],
    ],
    'link_status' => ['pending' => 'بانتظار الاعتماد', 'approved' => 'معتمد', 'rejected' => 'مرفوض'],
];
