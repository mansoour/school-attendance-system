<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Excuse;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Notifications\SchoolNotification;
use App\School\Calendar;
use App\School\GuardianLinker;
use App\Settings\SettingsService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SchoolAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Classroom $classroom;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        // A fixed Thursday, so attendance can be recorded regardless of the day the suite runs.
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        $this->seed();
        $this->school = School::create(['name' => 'Sample School']);
        $grade = Grade::create(['school_id' => $this->school->id, 'name' => 'Fourth primary', 'stage' => 'primary']);
        $this->classroom = Classroom::create(['school_id' => $this->school->id, 'grade_id' => $grade->id, 'name' => 'A']);
        $this->student = Student::create(['school_id' => $this->school->id, 'classroom_id' => $this->classroom->id, 'name' => 'Sara Ahmed', 'national_id' => '1000000001', 'phone' => '0551234567']);
    }

    private function staff(string $role, ?School $school = null): User
    {
        $user = User::factory()->create(['school_id' => ($school ?? $this->school)->id]);
        $user->assignRole($role);

        return $user;
    }

    private function guardian(): User
    {
        $guardian = User::factory()->create(['phone' => '0509999999']);
        $guardian->assignRole('guardian');
        $guardian->children()->attach($this->student->id, ['relation' => 'father', 'status' => 'approved', 'approved_at' => now()]);

        return $guardian;
    }

    public function test_every_school_page_renders_in_arabic_without_missing_phrases(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $record = AttendanceRecord::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'date' => today(), 'status' => 'absent']);
        Excuse::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'attendance_record_id' => $record->id, 'date' => today(), 'reason' => 'Fever']);
        $this->actingAs($owner)->withSession(['locale' => 'ar', 'school_id' => $this->school->id, 'auth.password_confirmed_at' => time()]);
        foreach (['school.dashboard', 'school.attendance.index', 'school.attendance.classes', 'school.excuses.index', 'school.notes.index', 'school.communication.index', 'school.reports.index', 'school.students.index', 'school.students.create', 'school.students.import', 'school.classes.index', 'school.grades.index', 'school.employees.index', 'school.guardians.index', 'school.schools.index', 'school.settings', 'school.activity', 'school.notifications'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('school.attendance.take', $this->classroom))->assertOk()->assertSee('Sara Ahmed');
        $this->get(route('school.students.show', $this->student))->assertOk();
        $this->get(route('school.students.edit', $this->student))->assertOk();
        $this->get(route('admin.settings', 'school-notifications'))->assertOk();
        $this->get(route('admin.settings', 'guardians'))->assertOk();
        $this->post('/logout');
        $this->get('/register')->assertOk()->assertSee('student_national_id', false);
        $this->get('/')->assertOk();
        $this->assertSame([], DB::table('missing_translations')->where('locale', 'ar')->pluck('key')->all());
    }

    public function test_owner_viewing_all_schools_picks_a_school_for_settings_and_has_no_children_menu(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner)->withSession(['locale' => 'ar'])->get(route('school.settings'))->assertOk()
            ->assertSee('Sample School')->assertDontSee(route('school.guardian.index'), false);
        $this->post(route('school.switch'), ['school_id' => $this->school->id]);
        $this->get(route('school.settings'))->assertOk()->assertSee('name="day_starts_at"', false);

        $guardian = $this->guardian();
        $this->actingAs($guardian)->get(route('school.guardian.index'))->assertOk()->assertSee(route('school.guardian.index'), false);
    }

    public function test_activity_log_shows_translated_actions_with_a_selectable_filter(): void
    {
        $principal = $this->staff('principal');
        $this->actingAs($principal)->post(route('school.grades.store'), ['name' => 'Fifth primary', 'stage' => 'primary']);
        $this->withSession(['locale' => 'ar'])->get(route('school.activity'))->assertOk()
            ->assertSee('إضافة: صف')->assertSee('<option value="grade.created"', false);
        $this->get(route('school.activity', ['action' => 'grade.created']))->assertOk()->assertSee('إضافة: صف');
    }

    public function test_default_content_and_email_templates_are_installed(): void
    {
        $this->get('/pages/privacy')->assertOk()->assertSee('سياسة الخصوصية');
        $this->assertDatabaseHas('email_templates', ['event' => 'student_absent', 'is_active' => true]);
        $this->assertDatabaseCount('site_notices', 1);
    }

    public function test_attendance_is_only_recorded_sunday_to_thursday(): void
    {
        $affairs = $this->staff('student_affairs');
        $friday = '2026-10-16';
        $this->actingAs($affairs)->post(route('school.attendance.save', $this->classroom), ['date' => '2026-10-10', 'entries' => [$this->student->id => ['status' => 'absent']]])
            ->assertSessionHasErrors('date');
        $this->assertDatabaseCount('attendance_records', 0);

        // Weekend pages show the last school day (Thursday) instead.
        $this->travelTo(Carbon::parse($friday.' 09:00'));
        $this->get(route('school.attendance.take', $this->classroom))->assertOk()->assertSee('value="2026-10-15"', false);
        $this->assertTrue(Calendar::isSchoolDay(Carbon::parse('2026-10-11')));
        $this->assertSame('2026-10-15', Calendar::lastSchoolDay(Carbon::parse('2026-10-17'))->toDateString());
    }

    public function test_taking_attendance_notifies_guardians_and_is_audited(): void
    {
        Notification::fake();
        $guardian = $this->guardian();
        $affairs = $this->staff('student_affairs');

        $this->actingAs($affairs)->post(route('school.attendance.save', $this->classroom), [
            'date' => today()->toDateString(),
            'entries' => [$this->student->id => ['status' => 'absent', 'note' => 'No contact']],
        ])->assertRedirect();

        $record = AttendanceRecord::where('student_id', $this->student->id)->sole();
        $this->assertSame('absent', $record->status);
        $this->assertSame(today()->toDateString(), $record->getRawOriginal('date'));
        Notification::assertSentTo($guardian, SchoolNotification::class, fn ($n, $channels) => $n->event === 'student_absent' && in_array('database', $channels) && in_array('mail', $channels));
        $this->assertTrue(AuditLog::where('action', 'attendance.class_saved')->exists());
        $this->assertTrue(AuditLog::where('action', 'attendance_record.created')->exists());

        // Saving the same status again does not notify a second time.
        Notification::fake();
        $this->post(route('school.attendance.save', $this->classroom), ['date' => today()->toDateString(), 'entries' => [$this->student->id => ['status' => 'absent']]]);
        Notification::assertNothingSent();
    }

    public function test_disabled_notification_events_are_not_sent(): void
    {
        Notification::fake();
        app(SettingsService::class)->set('school_notify.student_absent.enabled', false);
        $guardian = $this->guardian();
        $this->actingAs($this->staff('student_affairs'))->post(route('school.attendance.save', $this->classroom), ['date' => today()->toDateString(), 'entries' => [$this->student->id => ['status' => 'absent']]]);
        Notification::assertNotSentTo($guardian, SchoolNotification::class);
    }

    public function test_guardian_excuse_reaches_staff_and_approval_marks_the_day_excused(): void
    {
        Notification::fake();
        $guardian = $this->guardian();
        $health = $this->staff('health_guide');
        $record = AttendanceRecord::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'date' => today(), 'status' => 'absent']);

        $this->actingAs($guardian)->post(route('school.guardian.excuse.store', $record), ['type' => 'medical', 'reason' => 'Doctor visit', 'attachment' => UploadedFile::fake()->create('report.pdf', 20, 'application/pdf')])->assertRedirect(route('school.guardian.index'));
        $excuse = Excuse::sole();
        Notification::assertSentTo($health, SchoolNotification::class, fn ($n) => $n->event === 'excuse_submitted');

        $this->actingAs($health)->patch(route('school.excuses.review', $excuse), ['status' => 'approved'])->assertRedirect();
        $this->assertSame('excused', $record->fresh()->status);
        $this->assertSame('approved', $excuse->fresh()->status);
        Notification::assertSentTo($guardian, SchoolNotification::class, fn ($n) => $n->event === 'excuse_reviewed');
        $this->actingAs($guardian)->get(route('school.excuses.attachment', $excuse))->assertOk();
    }

    public function test_guardians_only_reach_their_own_children(): void
    {
        $guardian = $this->guardian();
        $other = Student::create(['school_id' => $this->school->id, 'name' => 'Other', 'national_id' => '1000000002']);
        $record = AttendanceRecord::create(['school_id' => $this->school->id, 'student_id' => $other->id, 'date' => today(), 'status' => 'absent']);
        $this->actingAs($guardian)->get(route('school.guardian.excuse', $record))->assertNotFound();
        $this->actingAs($guardian)->get(route('school.students.index'))->assertForbidden();
        $this->actingAs($guardian)->get(route('school.dashboard'))->assertRedirect(route('school.guardian.index'));
    }

    public function test_staff_are_limited_to_their_school_and_their_role(): void
    {
        $otherSchool = School::create(['name' => 'Other School']);
        $outsider = $this->staff('principal', $otherSchool);
        $this->actingAs($outsider)->get(route('school.students.show', $this->student))->assertNotFound();
        $this->actingAs($outsider)->get(route('school.students.index'))->assertOk()->assertDontSee('Sara Ahmed');

        $health = $this->staff('health_guide');
        $this->actingAs($health)->post(route('school.attendance.save', $this->classroom), ['date' => today()->toDateString(), 'entries' => [$this->student->id => ['status' => 'absent']]])->assertForbidden();
        $this->actingAs($health)->get(route('school.students.create'))->assertForbidden();
        $this->actingAs($health)->get(route('admin.index'))->assertForbidden();
    }

    public function test_principal_creates_employees_but_not_other_principals(): void
    {
        Notification::fake();
        $principal = $this->staff('principal');
        $this->actingAs($principal)->post(route('school.employees.store'), ['name' => 'New Vice', 'email' => 'vice@example.com', 'role' => 'vice_principal'])->assertSessionHasNoErrors();
        $vice = User::where('email', 'vice@example.com')->sole();
        $this->assertTrue($vice->hasRole('vice_principal'));
        $this->assertSame($this->school->id, $vice->school_id);
        $this->assertNotNull($vice->email_verified_at);
        $this->actingAs($principal)->post(route('school.employees.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'principal'])->assertSessionHasErrors('role');

        $this->actingAs($principal)->put(route('school.employees.update', $vice), ['name' => 'New Vice', 'email' => 'vice@example.com', 'role' => 'vice_principal', 'is_active' => 0])->assertRedirect();
        $this->post('/logout');
        $this->post('/login', ['email' => 'vice@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_guardian_link_activates_on_verification_and_fills_student_contact(): void
    {
        Notification::fake();
        $guardian = User::factory()->unverified()->create(['name' => 'Ahmed', 'phone' => '0501112222']);
        $guardian->assignRole('guardian');
        app(GuardianLinker::class)->request($guardian, $this->student, 'father');
        $this->assertSame('pending', $guardian->children()->first()->pivot->status);

        $guardian->markEmailAsVerified();
        event(new Verified($guardian));

        $this->assertSame('approved', $guardian->children()->first()->pivot->status);
        $student = $this->student->fresh();
        $this->assertSame('Ahmed', $student->guardian_name);
        $this->assertSame('0501112222', $student->guardian_phone);
        $this->assertSame('father', $student->guardian_relation);
    }

    public function test_guardian_links_wait_for_staff_when_approval_is_required(): void
    {
        Notification::fake();
        app(SettingsService::class)->set('guardians.require_approval', true);
        $affairs = $this->staff('student_affairs');
        $guardian = User::factory()->create();
        $guardian->assignRole('guardian');
        app(GuardianLinker::class)->request($guardian, $this->student, 'mother');
        app(GuardianLinker::class)->activate($guardian);
        $this->assertSame('pending', $guardian->children()->first()->pivot->status);
        Notification::assertSentTo($affairs, SchoolNotification::class, fn ($n) => $n->event === 'guardian_registered');

        $this->actingAs($affairs)->post(route('school.guardians.decide', [$guardian, $this->student]), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $guardian->children()->first()->pivot->status);
    }

    public function test_registration_matching_supports_phone_mode(): void
    {
        app(SettingsService::class)->set('guardians.match_mode', 'national_id_phone');
        $linker = app(GuardianLinker::class);
        $this->assertTrue($linker->match('1000000001', null, '+966 55 123 4567')?->is($this->student));
        $this->assertNull($linker->match('1000000001', 'Sara Ahmed', '0550000000'));
    }

    public function test_students_are_imported_from_excel(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['اسم الطالبة', 'رقم الهوية', 'الصف', 'الفصل', 'جوال ولي الأمر'],
            ['New Student', '2000000001', 'Fifth primary', 'B', '0503334444'],
            ['Sara Ahmed Updated', '1000000001', 'Fourth primary', 'A', ''],
            ['', '', '', '', ''],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($book))->save($path);

        $this->actingAs($this->staff('student_affairs'))->post(route('school.students.import.store'), ['file' => new UploadedFile($path, 'students.xlsx', null, null, true), 'create_classes' => 1])->assertSessionHasNoErrors();

        $new = Student::where('national_id', '2000000001')->sole();
        $this->assertSame('Fifth primary / B', $new->classroom->load('grade')->label());
        $this->assertSame('0503334444', $new->guardian_phone);
        $this->assertSame('Sara Ahmed Updated', $this->student->fresh()->name);
        $this->assertTrue(AuditLog::where('action', 'students.imported')->exists());
    }

    public function test_reports_export_to_excel_and_arabic_pdf(): void
    {
        AttendanceRecord::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'date' => today(), 'status' => 'absent']);
        $principal = $this->staff('principal');
        $this->actingAs($principal)->withSession(['locale' => 'ar'])->get(route('school.reports.index'))->assertOk()->assertSee('Sara Ahmed');
        $excel = $this->get(route('school.reports.export', 'xlsx'));
        $excel->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $pdf = $this->get(route('school.reports.export', 'pdf'));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->actingAs($this->staff('health_guide'))->get(route('school.reports.export', 'xlsx'))->assertForbidden();
    }

    public function test_communication_page_lists_absences_with_contact_links_and_logs_contacts(): void
    {
        $this->student->update(['guardian_phone' => '0551112233', 'guardian_email' => 'parent@example.com']);
        AttendanceRecord::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'classroom_id' => $this->classroom->id, 'date' => today(), 'status' => 'absent']);
        $vice = $this->staff('vice_principal');
        $this->actingAs($vice)->get(route('school.communication.index'))->assertOk()
            ->assertSee('https://wa.me/966551112233', false)->assertSee('tel:0551112233', false)->assertSee('mailto:parent@example.com', false);
        $this->postJson(route('school.communication.log'), ['student_id' => $this->student->id, 'channel' => 'whatsapp', 'absence_date' => today()->toDateString()])->assertOk();
        $this->assertDatabaseHas('guardian_contacts', ['student_id' => $this->student->id, 'channel' => 'whatsapp', 'user_id' => $vice->id]);
    }

    public function test_notes_shared_with_guardians_notify_them(): void
    {
        Notification::fake();
        $guardian = $this->guardian();
        $counselor = $this->staff('counselor');
        $this->actingAs($counselor)->post(route('school.notes.store'), ['student_id' => $this->student->id, 'category' => 'counseling', 'body' => 'Follow-up plan agreed.', 'visible_to_guardian' => 1])->assertRedirect();
        Notification::assertSentTo($guardian, SchoolNotification::class, fn ($n) => $n->event === 'note_shared');
        $this->actingAs($guardian)->get(route('school.guardian.index'))->assertOk()->assertSee('Follow-up plan agreed.');
    }

    public function test_repeated_absence_alerts_counselor_at_the_threshold(): void
    {
        Notification::fake();
        $counselor = $this->staff('counselor');
        $affairs = $this->staff('student_affairs');
        foreach ([2, 1, 0] as $daysAgo) {
            $this->actingAs($affairs)->post(route('school.attendance.save', $this->classroom), ['date' => today()->subDays($daysAgo)->toDateString(), 'entries' => [$this->student->id => ['status' => 'absent']]]);
        }
        Notification::assertSentToTimes($counselor, SchoolNotification::class, 1);
    }
}
