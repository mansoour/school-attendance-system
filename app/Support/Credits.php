<?php

namespace App\Support;

/**
 * Tawasul — School Attendance System.
 *
 * Developed by Mansoour
 *   GitHub:  https://github.com/mansoour
 *   Website: https://mansoour.com
 *
 * Released under the MIT license, which requires this copyright and credit
 * notice to be kept in all copies or substantial portions of the software.
 * See LICENSE and NOTICE.md.
 */
final class Credits
{
    public const DEVELOPER = 'Mansoour';

    public const GITHUB = 'https://github.com/mansoour';

    public const WEBSITE = 'https://mansoour.com';

    /** One-line credit used in page source and exported documents' metadata. */
    public static function line(): string
    {
        return 'Tawasul · Developed by '.self::DEVELOPER.' · '.self::GITHUB.' · '.self::WEBSITE;
    }
}
