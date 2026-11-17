<?php

declare(strict_types=1);

namespace App\DTOs\Review;

final readonly class StoreReviewData
{
    public function __construct(
        public string $patientName,
        public int $rating,
        public string $comment,
        public bool $isAnonymous,
        public ?int $userId,
    ) {
    }

    public static function fromValidated(array $data, ?int $userId): self
    {
        return new self(
            patientName: $data['patient_name'],
            rating: (int) $data['rating'],
            comment: $data['comment'],
            isAnonymous: (bool) ($data['is_anonymous'] ?? false),
            userId: $userId,
        );
    }
}
