<?php

return [
    'roles' => [
        'owner' => 'System administrator', 'principal' => 'Principal', 'vice_principal' => 'Vice principal', 'student_affairs' => 'Student affairs',
        'health_guide' => 'Health advisor', 'counselor' => 'Student counselor', 'guardian' => 'Guardian',
        'support' => 'Support', 'translator' => 'Translator', 'content_reviewer' => 'Content reviewer',
    ],
    'role_descriptions' => [
        'principal' => 'General supervision, reports and statistics',
        'vice_principal' => 'Absence follow-up and guardian communication',
        'student_affairs' => 'Recording absences and late arrivals, documenting actions',
        'health_guide' => 'Following health cases and health awareness',
        'counselor' => 'Following causes, counseling and guidance plans',
        'guardian' => 'Following absences, sending excuses and contacting the school',
    ],
    'status' => ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'excused' => 'Excused'],
    'excuse_status' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
    'excuse_type' => ['medical' => 'Medical', 'family' => 'Family matter', 'travel' => 'Travel', 'official' => 'Official duty', 'other' => 'Other'],
    'relation' => ['father' => 'Father', 'mother' => 'Mother', 'brother' => 'Brother', 'sister' => 'Sister', 'grandparent' => 'Grandparent', 'uncle' => 'Uncle', 'aunt' => 'Aunt', 'other' => 'Other'],
    'stage' => ['kindergarten' => 'Kindergarten', 'primary' => 'Primary', 'intermediate' => 'Intermediate', 'secondary' => 'Secondary'],
    'note_category' => ['general' => 'General', 'health' => 'Health', 'counseling' => 'Counseling', 'behavior' => 'Behavior', 'attendance' => 'Attendance'],
    'channel' => ['whatsapp' => 'WhatsApp', 'call' => 'Call', 'email' => 'Email'],
    'activity' => [
        'verbs' => [
            'created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'saved' => 'Saved', 'reset' => 'Reset to default',
            'class_saved' => 'Class attendance saved', 'imported' => 'Imported', 'exported' => 'Exported', 'settings_saved' => 'Settings saved',
            'password_link_sent' => 'Password link sent', 'link_requested' => 'Link requested', 'link_approved' => 'Link approved', 'link_rejected' => 'Link rejected',
            'assigned' => 'Assigned', 'roles_updated' => 'Roles updated', 'unlocked' => 'Account unlocked', 'account_locked' => 'Account locked',
            'enabled' => 'Enabled', 'removed' => 'Removed', 'published' => 'Published', 'recovery_code_used' => 'Recovery code used',
            'recovery_codes_regenerated' => 'Recovery codes regenerated',
        ],
        'entities' => [
            'school' => 'School', 'grade' => 'Grade', 'classroom' => 'Class', 'student' => 'Student', 'students' => 'Students',
            'attendance_record' => 'Attendance record', 'attendance' => 'Attendance', 'excuse' => 'Excuse', 'student_note' => 'Note',
            'guardian_contact' => 'Guardian contact', 'guardian' => 'Guardian', 'employees' => 'Employee', 'reports' => 'Report',
            'owner' => 'System administrator', 'members' => 'Member', 'roles' => 'Role', 'auth' => 'Sign-in', 'mfa' => 'Two-factor authentication',
            'integrations' => 'Integrations', 'language.visibility' => 'Language visibility', 'translations' => 'Translations', 'support' => 'Support',
            'pages' => 'Page', 'site-notices' => 'Site notice', 'email-templates' => 'Email template', 'user' => 'User',
            'settings' => 'Security settings', 'appearance' => 'Appearance', 'maintenance' => 'Maintenance', 'branding' => 'Branding',
            'homepage' => 'Homepage', 'guardians' => 'Guardian registration', 'school-notifications' => 'School notifications',
            'staff-alerts' => 'Staff alerts', 'storage' => 'Storage', 'icons' => 'Icons', 'cookies' => 'Cookies',
            'social' => 'Social links', 'styling' => 'Colors', 'fonts' => 'Fonts',
        ],
    ],
    'link_status' => ['pending' => 'Awaiting approval', 'approved' => 'Approved', 'rejected' => 'Rejected'],
];
