<?php

namespace App\School;

/**
 * School roles and the permissions each one receives by default.
 * Grants stay editable afterwards from Administration → Roles.
 */
class Roles
{
    public const PERMISSIONS = [
        'school.access', 'schools.manage', 'school.settings', 'employees.manage',
        'grades.manage', 'classes.manage', 'students.view', 'students.manage', 'students.import',
        'attendance.view', 'attendance.take', 'excuses.view', 'excuses.review', 'excuses.submit',
        'notes.view', 'notes.manage', 'communication.access', 'reports.view', 'reports.export',
        'guardians.approve', 'guardian.portal',
    ];

    /** Roles a principal may assign when creating employee accounts. */
    public const STAFF = ['principal', 'vice_principal', 'student_affairs', 'health_guide', 'counselor'];

    public static function grants(): array
    {
        $staffCore = ['school.access', 'students.view', 'attendance.view', 'excuses.view', 'notes.view', 'reports.view'];

        return [
            'principal' => array_values(array_diff(self::PERMISSIONS, ['schools.manage', 'excuses.submit', 'guardian.portal'])),
            'vice_principal' => [...$staffCore, 'attendance.take', 'excuses.review', 'notes.manage', 'communication.access', 'reports.export', 'guardians.approve'],
            'student_affairs' => [...$staffCore, 'grades.manage', 'classes.manage', 'students.manage', 'students.import', 'attendance.take', 'excuses.review', 'notes.manage', 'communication.access', 'reports.export', 'guardians.approve'],
            'health_guide' => [...$staffCore, 'excuses.review', 'notes.manage'],
            'counselor' => [...$staffCore, 'notes.manage', 'communication.access', 'reports.export'],
            'guardian' => ['school.access', 'guardian.portal', 'excuses.submit'],
        ];
    }

    public static function all(): array
    {
        return array_merge(self::STAFF, ['guardian']);
    }
}
