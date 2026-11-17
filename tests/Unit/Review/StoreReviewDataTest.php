<?php

namespace Tests\Unit\Review;

use App\DTOs\Review\StoreReviewData;
use PHPUnit\Framework\TestCase;

class StoreReviewDataTest extends TestCase
{
    public function test_it_maps_validated_public_review_fields(): void
    {
        $data = StoreReviewData::fromValidated([
            'patient_name' => 'Test Patient',
            'rating' => '5',
            'comment' => 'A useful review for the doctor.',
            'is_anonymous' => '1',
        ], 17);

        $this->assertSame('Test Patient', $data->patientName);
        $this->assertSame(5, $data->rating);
        $this->assertSame('A useful review for the doctor.', $data->comment);
        $this->assertTrue($data->isAnonymous);
        $this->assertSame(17, $data->userId);
    }

    public function test_it_supports_anonymous_guest_submission_without_user_id(): void
    {
        $data = StoreReviewData::fromValidated([
            'patient_name' => 'Anonymous',
            'rating' => 4,
            'comment' => 'This is another valid review.',
        ], null);

        $this->assertFalse($data->isAnonymous);
        $this->assertNull($data->userId);
    }
}
