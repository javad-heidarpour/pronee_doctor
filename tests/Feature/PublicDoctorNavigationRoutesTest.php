<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class PublicDoctorNavigationRoutesTest extends TestCase
{
    public function test_public_doctor_navigation_routes_require_and_preserve_the_doctor_slug(): void
    {
        $slug = 'sample-doctor';

        $this->assertSame(url('/sample-doctor'), route('home1', ['doctor' => $slug], false));
        $this->assertSame(url('/sample-doctor/booking'), route('booking', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/services'), route('services', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/surgeries'), route('Surgeries', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/contact'), route('contact', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/About'), route('About', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/WorkingHours'), route('WorkingHours', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/tips'), route('tips', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/reviews'), route('Reviews', ['slug' => $slug], false));
        $this->assertSame(url('/sample-doctor/galleries'), route('galleries', ['slug' => $slug], false));
    }
}
