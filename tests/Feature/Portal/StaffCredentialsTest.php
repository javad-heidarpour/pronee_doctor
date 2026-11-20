<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\StaffCredentialsController;
use App\Models\ClinicOrganization;
use App\Models\ClinicStaffCredential;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class StaffCredentialsTest extends TestCase
{
    private User $owner;
    private User $staff;
    private Doctor $doctor;
    private ClinicOrganization $organization;
    private int $clinicId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('family')->nullable();
            $table->string('email')->unique(); $table->string('password')->nullable();
            $table->string('role')->default('user'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('user_id');
            $table->primary(['doctor_id', 'user_id']);
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable(); $table->string('family')->nullable();
            $table->string('slug')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->string('name')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('owner_user_id'); $table->string('name');
            $table->string('slug'); $table->string('timezone')->nullable(); $table->string('status')->default('active');
            $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('doctor_id');
            $table->string('role')->default('practitioner'); $table->unsignedInteger('revenue_share_basis_points')->default(7000);
            $table->string('status')->default('active'); $table->date('joined_at')->nullable();
            $table->timestamps(); $table->primary(['organization_id', 'doctor_id']);
        });
        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('clinic_id');
            $table->string('branch_code')->nullable(); $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active'); $table->timestamps();
            $table->primary(['organization_id', 'clinic_id']);
        });
        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable(); $table->text('permissions')->nullable(); $table->text('clinic_ids')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
            $table->primary(['organization_id', 'user_id']);
        });
        Schema::create('clinic_staff_credentials', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id'); $table->unsignedBigInteger('uploaded_by_user_id');
            $table->string('credential_type'); $table->string('title'); $table->string('issuer')->nullable();
            $table->string('license_number')->nullable(); $table->date('issued_at')->nullable(); $table->date('expires_at')->nullable();
            $table->string('original_name'); $table->string('disk'); $table->string('path');
            $table->string('mime_type'); $table->unsignedBigInteger('file_size'); $table->string('checksum_sha256', 64);
            $table->string('status')->default('pending'); $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->timestamp('verified_at')->nullable(); $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('archived_by_user_id')->nullable(); $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        $this->owner = User::query()->create([
            'name' => 'Organization Owner', 'email' => 'owner-credentials@example.test',
            'role' => 'doctor', 'is_active' => true,
        ]);
        $this->staff = User::query()->create([
            'name' => 'Staff Member', 'email' => 'staff-credentials@example.test',
            'role' => 'secretary', 'is_active' => true,
        ]);
        $this->doctor = Doctor::query()->create([
            'user_id' => $this->owner->id, 'name' => 'Owner', 'family' => 'Doctor',
            'slug' => 'owner-credentials', 'is_active' => true,
        ]);
        DB::table('doctor_user')->insert(['doctor_id' => $this->doctor->id, 'user_id' => $this->owner->id]);
        $this->organization = ClinicOrganization::query()->create([
            'owner_user_id' => $this->owner->id, 'name' => 'Credentials Organization',
            'slug' => 'credentials-org', 'timezone' => 'Asia/Tehran', 'status' => 'active',
        ]);
        $this->clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $this->doctor->id, 'name' => 'Main clinic', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $this->organization->id, 'doctor_id' => $this->doctor->id,
            'role' => 'owner', 'revenue_share_basis_points' => 10000, 'status' => 'active',
            'joined_at' => '2026-01-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_clinics')->insert([
            'organization_id' => $this->organization->id, 'clinic_id' => $this->clinicId,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_members')->insert([
            'organization_id' => $this->organization->id, 'user_id' => $this->staff->id,
            'role' => 'receptionist', 'permissions' => json_encode([]),
            'clinic_ids' => json_encode([$this->clinicId]), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_owner_can_upload_private_credential_for_an_active_staff_member(): void
    {
        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id' => $this->doctor->id]);

        app(StaffCredentialsController::class)->store($this->request([
            'organization_id' => $this->organization->id,
            'clinic_id' => $this->clinicId,
            'staff_user_id' => $this->staff->id,
            'credential_type' => 'medical_license',
            'title' => 'پروانه مسئول فنی',
            'issuer' => 'سازمان نظام پزشکی',
            'license_number' => 'LIC-123',
            'issued_at' => '2025-01-01',
            'expires_at' => '2027-01-01',
            'file' => UploadedFile::fake()->create('license.pdf', 80, 'application/pdf'),
        ]), app(RecordSecurityAuditEventAction::class));

        $credential = ClinicStaffCredential::query()->firstOrFail();
        $this->assertSame('pending', $credential->status);
        $this->assertSame($this->staff->id, (int) $credential->staff_user_id);
        $this->assertSame('local', $credential->disk);
        $this->assertStringStartsWith('staff-credentials/', $credential->path);
        Storage::disk('local')->assertExists($credential->path);
    }

    public function test_staff_can_upload_only_for_themselves_and_only_to_an_assigned_clinic(): void
    {
        Auth::guard('web')->setUser($this->staff);
        session(['active_doctor_id' => $this->doctor->id]);

        app(StaffCredentialsController::class)->store($this->request([
            'organization_id' => $this->organization->id,
            'clinic_id' => $this->clinicId,
            'staff_user_id' => $this->owner->id,
            'credential_type' => 'training_certificate',
            'title' => 'گواهی دوره',
            'file' => UploadedFile::fake()->create('course.pdf', 40, 'application/pdf'),
        ]));

        $this->assertDatabaseHas('clinic_staff_credentials', [
            'staff_user_id' => $this->staff->id,
            'uploaded_by_user_id' => $this->staff->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('clinic_staff_credentials', ['staff_user_id' => $this->owner->id]);
    }

    public function test_only_owner_can_review_pending_credential_and_rejection_requires_reason(): void
    {
        $credential = $this->credential();
        Auth::guard('web')->setUser($this->staff);
        session(['active_doctor_id' => $this->doctor->id]);

        try {
            app(StaffCredentialsController::class)->review(
                Request::create('/portal/staff-credentials/'.$credential->id.'/review', 'PATCH', ['decision' => 'verified']),
                $credential->id,
                app(\App\Actions\Security\RecordSecurityAuditEventAction::class)
            );
            $this->fail('A staff member must not review credentials.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        Auth::guard('web')->setUser($this->owner);
        try {
            app(StaffCredentialsController::class)->review(
                Request::create('/portal/staff-credentials/'.$credential->id.'/review', 'PATCH', ['decision' => 'rejected']),
                $credential->id,
                app(\App\Actions\Security\RecordSecurityAuditEventAction::class)
            );
            $this->fail('A rejection must include a reason.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('rejection_reason', $exception->errors());
        }

        app(StaffCredentialsController::class)->review(
            Request::create('/portal/staff-credentials/'.$credential->id.'/review', 'PATCH', [
                'decision' => 'rejected', 'rejection_reason' => 'مدرک تاریخ معتبر ندارد',
            ]),
            $credential->id,
            app(\App\Actions\Security\RecordSecurityAuditEventAction::class)
        );

        $this->assertDatabaseHas('clinic_staff_credentials', [
            'id' => $credential->id, 'status' => 'rejected', 'verified_by_user_id' => $this->owner->id,
        ]);
    }

    public function test_staff_cannot_download_another_members_credential(): void
    {
        $credential = $this->credential();
        $other = User::query()->create([
            'name' => 'Other Staff', 'email' => 'other-staff@example.test',
            'role' => 'secretary', 'is_active' => true,
        ]);
        $credential->update(['staff_user_id' => $other->id]);
        Auth::guard('web')->setUser($this->staff);
        session(['active_doctor_id' => $this->doctor->id]);

        try {
            app(StaffCredentialsController::class)->download(
                $credential->id,
                app(\App\Actions\Security\RecordSecurityAuditEventAction::class)
            );
            $this->fail('Staff must not download another member credential.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_archiving_keeps_original_private_file(): void
    {
        $credential = $this->credential();
        Storage::disk('local')->put($credential->path, 'private credential bytes');
        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id' => $this->doctor->id]);

        app(StaffCredentialsController::class)->archive(
            $credential->id,
            app(\App\Actions\Security\RecordSecurityAuditEventAction::class)
        );

        $this->assertDatabaseHas('clinic_staff_credentials', [
            'id' => $credential->id,
            'archived_by_user_id' => $this->owner->id,
        ]);
        Storage::disk('local')->assertExists($credential->path);
    }

    private function credential(): ClinicStaffCredential
    {
        $path = 'staff-credentials/'.$this->organization->id.'/'.$this->staff->id.'/credential.pdf';
        Storage::disk('local')->put($path, 'private credential bytes');

        return ClinicStaffCredential::query()->create([
            'organization_id' => $this->organization->id,
            'clinic_id' => $this->clinicId,
            'staff_user_id' => $this->staff->id,
            'uploaded_by_user_id' => $this->staff->id,
            'credential_type' => 'medical_license',
            'title' => 'Test license',
            'original_name' => 'license.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 20,
            'checksum_sha256' => hash('sha256', 'private credential bytes'),
            'status' => 'pending',
        ]);
    }

    private function request(array $data): Request
    {
        return Request::create(
            '/portal/staff-credentials',
            'POST',
            array_filter($data, fn ($value) => !$value instanceof UploadedFile),
            [],
            isset($data['file']) ? ['file' => $data['file']] : [],
            ['HTTP_REFERER' => '/portal/staff-credentials']
        );
    }
}
