<?php

namespace App\Support;

use App\Models\EmailTemplate;
use App\Models\Page;
use App\Models\SiteNotice;
use App\School\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * The platform's ready-to-use content: public pages, email templates for every
 * notification event and a welcome site notice. Existing edits are never overwritten.
 */
class DefaultContent
{
    public static function install(): void
    {
        foreach (self::pages() as $slug => $page) {
            $existing = Page::where('slug', $slug)->first();
            if (! $existing) {
                Page::create(['slug' => $slug] + $page + ['is_published' => true]);
            } elseif (self::isPlaceholder($existing)) {
                $existing->update($page + ['is_published' => true]);
            }
        }
        foreach (self::emailTemplates() as $event => $template) {
            EmailTemplate::firstOrCreate(['event' => $event], $template + ['is_active' => true]);
        }
        if (! SiteNotice::exists()) {
            SiteNotice::create([
                'message' => ['ar' => 'أهلًا بكم في منصة تواصل: تابعوا حضور أبنائكم وأرسلوا الأعذار إلكترونيًا.', 'en' => 'Welcome to Tawasul: follow your children’s attendance and send excuses online.'],
                'link_label' => ['ar' => 'تسجيل ولي الأمر', 'en' => 'Guardian registration'],
                'link_url' => url('/register'),
                'tone' => 'info', 'position' => 'above', 'is_active' => true, 'dismissible' => true, 'sort_order' => 0,
            ]);
        }
    }

    /** Turns every school notification event on, in-app and by email. */
    public static function enableNotifications(): void
    {
        foreach (Notifier::events() as $event) {
            foreach (['enabled', 'database', 'mail'] as $channel) {
                DB::table('settings')->updateOrInsert(['key' => "school_notify.$event.$channel"], ['value' => json_encode(true), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private static function isPlaceholder(Page $page): bool
    {
        $body = json_encode($page->body, JSON_UNESCAPED_UNICODE);

        return str_contains($body, 'starter content') || str_contains($body, 'Replace this page') || str_contains($body, 'استبدل محتوى');
    }

    /** Tokens such as {student} and {date} are replaced with the event's details. */
    public static function emailTemplates(): array
    {
        $t = fn (string $subjectAr, string $bodyAr, string $subjectEn, string $bodyEn) => ['subject' => ['ar' => $subjectAr, 'en' => $subjectEn], 'body' => ['ar' => $bodyAr, 'en' => $bodyEn]];

        return [
            'welcome' => $t('مرحبًا بك في منصة تواصل', "مرحبًا {name}،\nتم إنشاء حسابك في منصة تواصل. يمكنك الآن متابعة الحضور والغياب والإشعارات من حسابك.", 'Welcome to Tawasul', "Hello {name},\nYour Tawasul account is ready. You can now follow attendance and notifications from your account."),
            'support_received' => $t('رسالة دعم جديدة', 'وصلت رسالة جديدة إلى صندوق الدعم وتنتظر الرد.', 'New support message', 'A new message is waiting in the support inbox.'),
            'security' => $t('تنبيه أمني لحسابك', "مرحبًا {name}،\nتغيرت إعدادات أمان حسابك أو حالة تسجيل الدخول. إذا لم تقم بذلك فتواصل مع إدارة المدرسة.", 'Security alert for your account', "Hello {name},\nYour account security settings or sign-in status changed. If this was not you, contact the school administration."),
            'student_absent' => $t('إشعار غياب: {student}', "ولي أمر الطالبة {student} الكريم،\nنود إبلاغكم بغياب الطالبة بتاريخ {date}. نأمل إرسال العذر عبر المنصة.", 'Absence notice: {student}', "Dear guardian of {student},\n{student} was absent on {date}. Please send the excuse through the platform."),
            'student_absent_staff' => $t('غياب الطالبة {student}', 'سُجل غياب الطالبة {student} ({classroom}) بتاريخ {date}.', 'Absence: {student}', '{student} ({classroom}) was absent on {date}.'),
            'student_late' => $t('إشعار تأخر: {student}', "ولي أمر الطالبة {student} الكريم،\nنود إبلاغكم بتأخر الطالبة بتاريخ {date}.", 'Late arrival: {student}', "Dear guardian of {student},\n{student} arrived late on {date}."),
            'excuse_submitted' => $t('عذر جديد للطالبة {student}', 'وصل عذر جديد للطالبة {student} عن يوم {date} وينتظر المراجعة.', 'New excuse for {student}', 'A new excuse for {student} on {date} is awaiting review.'),
            'excuse_reviewed' => $t('نتيجة مراجعة العذر', 'تمت مراجعة عذر الطالبة {student} عن يوم {date}: {status}.', 'Excuse review result', 'The excuse for {student} on {date} was reviewed: {status}.'),
            'note_shared' => $t('ملاحظة جديدة من المدرسة', 'أضافت المدرسة ملاحظة جديدة بخصوص الطالبة {student}. يمكنك الاطلاع عليها من حسابك.', 'New note from the school', 'The school added a new note about {student}. You can read it in your account.'),
            'guardian_registered' => $t('تسجيل ولي أمر جديد', 'سجل ولي أمر جديد ({guardian}) للطالبة {student}.', 'New guardian registration', 'A new guardian ({guardian}) registered for {student}.'),
            'guardian_link_approved' => $t('تم ربط حسابك بالطالبة', 'تم اعتماد ربط حسابك بالطالبة {student}. يمكنك الآن متابعة حضورها وإرسال الأعذار.', 'Your account is linked', 'Your account is now linked to {student}. You can follow attendance and send excuses.'),
            'repeated_absence' => $t('تنبيه تكرار غياب: {student}', 'تكرر غياب الطالبة {student} ({count} أيام خلال آخر 30 يومًا). نأمل المتابعة.', 'Repeated absence: {student}', '{student} has been absent {count} days in the last 30 days. Please follow up.'),
        ];
    }

    public static function pages(): array
    {
        return [
            'help' => ['title' => ['ar' => 'المساعدة', 'en' => 'Help'], 'body' => ['ar' => <<<'MD'
## كيف نستخدم منصة تواصل؟

### لأولياء الأمور
1. **التسجيل:** اضغط «تسجيل ولي الأمر» وأدخل اسمك وبريدك وجوالك وصلة القرابة، ثم بيانات الطالبة كما هي مسجلة في المدرسة (رقم الهوية والاسم أو الجوال حسب ما تحدده المدرسة).
2. **تأكيد البريد:** افتح رسالة التأكيد المرسلة إلى بريدك ليُفعَّل ربط حسابك بالطالبة.
3. **متابعة الحضور:** من صفحة «أبنائي» تظهر أيام الغياب والتأخر لكل ابنة خلال آخر 30 يومًا.
4. **إرسال العذر:** اضغط «إرسال عذر» بجانب يوم الغياب، اختر نوع العذر واكتب السبب، ويمكنك إرفاق تقرير طبي (PDF أو صورة).
5. **الإشعارات:** تصلك إشعارات داخل المنصة وبالبريد عند تسجيل الغياب أو التأخر وعند مراجعة العذر.
6. **إضافة ابن أو ابنة أخرى:** من صفحة «أبنائي» ← «إضافة ابن / ابنة».

### لمنسوبات المدرسة
- **تسجيل الحضور:** «تحضير الفصول» ← اختيار الفصل ← تحديد الغائبات والمتأخرات فقط ثم «حفظ التحضير». الجميع حاضرات افتراضيًا.
- **الأعذار:** «الأعذار» لمراجعة الأعذار الواردة من أولياء الأمور بالقبول أو الرفض، أو لتسجيل عذر ورد للمدرسة ورقيًا.
- **التواصل مع أولياء الأمور:** «التواصل مع أولياء الأمور» يعرض الغائبات حسب اليوم والصف والفصل مع أزرار واتساب والاتصال والبريد.
- **الملاحظات:** إضافة ملاحظات صحية أو إرشادية أو سلوكية، مع خيار مشاركتها مع ولي الأمر.
- **التقارير:** نسب الغياب حسب الصف والفصل والفترة، مع التصدير إلى Excel وPDF.

### أسئلة شائعة
**لم أتمكن من التسجيل؟** تأكد من كتابة رقم الهوية واسم الطالبة كما هما في سجلات المدرسة، أو تواصل مع شؤون الطالبات لتحديث البيانات.

**لم تصلني رسالة التأكيد؟** راجع مجلد الرسائل غير المرغوب فيها، أو اطلب إعادة الإرسال من صفحة تأكيد البريد.

**نسيت كلمة المرور؟** استخدم «نسيت كلمة المرور؟» في صفحة تسجيل الدخول.

لأي استفسار آخر استخدم صفحة **تواصل معنا**.
MD, 'en' => <<<'MD'
## How to use Tawasul

### For guardians
1. **Register:** choose “Guardian registration”, enter your name, email, mobile and relation, then the student’s details exactly as the school has them (national ID plus name or phone, as the school decides).
2. **Verify your email:** open the confirmation message to activate the link to your child.
3. **Follow attendance:** “My children” shows absences and late arrivals for the last 30 days.
4. **Send an excuse:** choose “Send excuse” next to the day, pick a type, write the reason and optionally attach a medical report (PDF or image).
5. **Notifications:** you are notified in the platform and by email when an absence or late arrival is recorded and when an excuse is reviewed.
6. **Add another child:** “My children” → “Add a child”.

### For school staff
- **Take attendance:** “Class attendance” → choose the class → mark only absent or late students → “Save attendance”. Everyone else is present by default.
- **Excuses:** review guardians’ excuses or record an excuse received at school.
- **Guardian communication:** absent students by day, grade and class with WhatsApp, call and email buttons.
- **Notes:** health, counseling or behavior notes, optionally shared with the guardian.
- **Reports:** absence rates by grade, class and period, exportable to Excel and PDF.

### Frequently asked questions
**Registration failed?** Make sure the national ID and name match the school’s records, or ask student affairs to update them.

**No confirmation email?** Check your spam folder or request a new link from the verification page.

**Forgot your password?** Use “Forgot your password?” on the sign-in page.

For anything else, use the **Contact** page.
MD]],
            'privacy' => ['title' => ['ar' => 'سياسة الخصوصية', 'en' => 'Privacy policy'], 'body' => ['ar' => <<<'MD'
## سياسة الخصوصية

نلتزم في منصة تواصل بحماية بيانات الطالبات وأولياء الأمور ومنسوبات المدرسة، واستخدامها فقط لغرض متابعة الحضور والتواصل بين المدرسة والأسرة.

### البيانات التي نجمعها
- **بيانات الطالبة:** الاسم، رقم الهوية، الصف والفصل، الجوال والبريد عند توفرهما، وسجل الحضور والغياب والأعذار والملاحظات.
- **بيانات ولي الأمر:** الاسم، البريد الإلكتروني، رقم الجوال، وصلة القرابة.
- **بيانات المنسوبات:** الاسم، البريد، الجوال، والمسمى الوظيفي.
- **بيانات تقنية:** سجل الإجراءات وعناوين IP لأغراض الأمان والتدقيق.

### كيف نستخدم البيانات
- تسجيل الحضور والغياب والتأخر ومتابعتها.
- إشعار أولياء الأمور بالغياب والتأخر ونتائج الأعذار.
- إعداد التقارير والإحصائيات لدعم انتظام الطالبات.
- حماية الحسابات وتوثيق الإجراءات.

### مشاركة البيانات
لا تُباع البيانات ولا تُشارك مع أي جهة تجارية. يطلع عليها فقط منسوبو المدرسة المخولون كلٌ حسب صلاحياته، وولي الأمر المرتبط بالطالبة. قد تُستخدم خدمات تقنية لإرسال البريد واستضافة النظام وفق ضوابط حماية مناسبة.

### الملاحظات والمرفقات
الملاحظات الصحية والإرشادية سرية، ولا تظهر لولي الأمر إلا إذا اختارت المختصة مشاركتها. مرفقات الأعذار محفوظة في مساحة خاصة ولا يطلع عليها إلا المخولون.

### الاحتفاظ بالبيانات
نحتفظ بالسجلات طوال فترة دراسة الطالبة في المدرسة وفق ما تقرره إدارة المدرسة والأنظمة المعمول بها.

### حقوقك
يحق لولي الأمر الاطلاع على بيانات أبنائه وطلب تصحيحها عبر إدارة المدرسة، ويمكنه تنزيل بيانات حسابه من صفحة «الحساب».

### التواصل
لأي استفسار عن الخصوصية تواصل مع إدارة المدرسة عبر صفحة **تواصل معنا**.
MD, 'en' => <<<'MD'
## Privacy policy

Tawasul protects the data of students, guardians and school staff and uses it only to follow attendance and connect the school with families.

### Data we collect
- **Students:** name, national ID, grade and class, phone and email when available, and attendance, excuse and note records.
- **Guardians:** name, email, mobile number and relation to the student.
- **Staff:** name, email, mobile number and job title.
- **Technical data:** activity logs and IP addresses for security and auditing.

### How we use data
- Recording and following attendance, absence and lateness.
- Informing guardians about absences, late arrivals and excuse decisions.
- Producing reports and statistics that support regular attendance.
- Protecting accounts and documenting actions.

### Sharing
Data is never sold or shared with commercial parties. Only authorized school staff (each within their permissions) and the student’s linked guardians can see it. Technical services for email delivery and hosting may be used under appropriate safeguards.

### Notes and attachments
Health and counseling notes are confidential and are shown to guardians only when staff choose to share them. Excuse attachments are stored privately and only authorized people can open them.

### Retention
Records are kept while the student studies at the school, as decided by the school administration and applicable regulations.

### Your rights
Guardians may view their children’s data and ask the school to correct it, and can download their account data from the Account page.

### Contact
For privacy questions, contact the school administration through the **Contact** page.
MD]],
            'terms' => ['title' => ['ar' => 'شروط الاستخدام', 'en' => 'Terms of use'], 'body' => ['ar' => <<<'MD'
## شروط الاستخدام

باستخدامك منصة تواصل فإنك توافق على الشروط التالية.

### الحسابات
- حسابات المنسوبات تُنشئها إدارة المدرسة، وحسابات أولياء الأمور تُنشأ بالتسجيل والتحقق من بيانات الطالبة.
- يلتزم المستخدم بصحة البيانات المدخلة، وبالمحافظة على سرية كلمة المرور وعدم مشاركتها.
- يحق للمدرسة إيقاف أي حساب يُساء استخدامه أو تثبت عدم صحة بياناته.

### الاستخدام المسموح
- تُستخدم المنصة لمتابعة الحضور والغياب والتواصل بين المدرسة وأولياء الأمور فقط.
- يُمنع إدخال بيانات غير صحيحة أو إرسال أعذار أو مرفقات مزورة.
- يُمنع محاولة الوصول إلى بيانات طالبات غير مرتبطات بالحساب.

### الأعذار والقرارات
قبول الأعذار أو رفضها قرار يعود للمدرسة وفق أنظمتها، ويظهر القرار لولي الأمر داخل المنصة.

### الإشعارات والتواصل
بالتسجيل يوافق ولي الأمر على استقبال إشعارات الحضور والغياب عبر المنصة والبريد الإلكتروني، وعلى تواصل المدرسة معه عبر الهاتف أو واتساب بخصوص انتظام الطالبة.

### السجلات
تُوثَّق جميع الإجراءات في سجل خاص لأغراض الأمان والتدقيق.

### التعديلات
قد تُحدَّث هذه الشروط عند الحاجة، ويُعد استمرار استخدام المنصة موافقة على النسخة المحدثة.

لأي استفسار تواصل معنا عبر صفحة **تواصل معنا**.
MD, 'en' => <<<'MD'
## Terms of use

By using Tawasul you agree to the following terms.

### Accounts
- Staff accounts are created by the school administration; guardian accounts are created by registering and verifying the student’s details.
- Users must enter accurate information and keep their password private.
- The school may suspend any account that is misused or whose details prove inaccurate.

### Permitted use
- The platform is for following attendance and for communication between the school and guardians only.
- Entering false information or submitting forged excuses or attachments is prohibited.
- Attempting to access students not linked to your account is prohibited.

### Excuses and decisions
Accepting or rejecting excuses is the school’s decision according to its regulations; the decision is shown to the guardian in the platform.

### Notifications and contact
By registering, guardians agree to receive attendance notifications in the platform and by email, and to be contacted by the school by phone or WhatsApp about the student’s attendance.

### Records
All actions are recorded in an activity log for security and auditing.

### Changes
These terms may be updated when needed; continuing to use the platform means accepting the updated version.

For questions, use the **Contact** page.
MD]],
        ];
    }
}
