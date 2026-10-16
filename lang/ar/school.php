<?php

return [
    'roles' => [
        'owner' => 'مدير النظام', 'principal' => 'مديرة المدرسة', 'vice_principal' => 'الوكيلة', 'student_affairs' => 'شؤون الطالبات',
        'health_guide' => 'الموجه الصحي', 'counselor' => 'المرشدة الطلابية', 'guardian' => 'ولي الأمر',
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
    'link_status' => ['pending' => 'بانتظار الاعتماد', 'approved' => 'معتمد', 'rejected' => 'مرفوض'],
];
