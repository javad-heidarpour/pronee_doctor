<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Doctor;
use App\Models\DoctorImage;
use App\Policies\DoctorImagePolicy;
use PHPUnit\Framework\TestCase;

final class DoctorImagePolicyTest extends TestCase
{
    public function test_only_the_owning_doctor_can_update_or_delete_an_image(): void
    {
        $owner = new Doctor();
        $owner->id = 10;

        $otherDoctor = new Doctor();
        $otherDoctor->id = 11;

        $image = new DoctorImage();
        $image->doctor_id = 10;

        $policy = new DoctorImagePolicy();

        $this->assertTrue($policy->update($owner, $image));
        $this->assertTrue($policy->delete($owner, $image));
        $this->assertFalse($policy->update($otherDoctor, $image));
        $this->assertFalse($policy->delete($otherDoctor, $image));
    }
}
