<?php

return [
    'roles' => [
        'owner' => 'System administrator', 'principal' => 'Principal', 'vice_principal' => 'Vice principal', 'student_affairs' => 'Student affairs',
        'health_guide' => 'Health advisor', 'counselor' => 'Student counselor', 'guardian' => 'Guardian',
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
    'link_status' => ['pending' => 'Awaiting approval', 'approved' => 'Approved', 'rejected' => 'Rejected'],
];
