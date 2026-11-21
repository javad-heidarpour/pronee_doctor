<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Http\Controllers\Portal\NotificationController;
use ReflectionMethod;
use Tests\TestCase;

final class NotificationUrlSafetyTest extends TestCase
{
    private function sanitize(mixed $url): ?string
    {
        $method = new ReflectionMethod(NotificationController::class, 'safeInternalUrl');
        $method->setAccessible(true);

        return $method->invoke(app(NotificationController::class), $url);
    }

    public function test_internal_relative_path_is_preserved(): void
    {
        $this->assertSame('/portal/appointments', $this->sanitize('/portal/appointments'));
    }

    public function test_external_and_protocol_relative_urls_are_rejected(): void
    {
        $this->assertNull($this->sanitize('https://evil.example'));
        $this->assertNull($this->sanitize('//evil.example/path'));
        $this->assertNull($this->sanitize('javascript:alert(1)'));
    }

    public function test_non_string_url_is_rejected(): void
    {
        $this->assertNull($this->sanitize(['url' => '/portal']));
    }
}
