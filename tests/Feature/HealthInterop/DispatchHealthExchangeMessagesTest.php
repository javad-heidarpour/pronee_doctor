<?php

declare(strict_types=1);

namespace Tests\Feature\HealthInterop;

use App\Models\HealthExchangeMessage;
use App\Services\HealthInterop\HealthExchangeGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class DispatchHealthExchangeMessagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('health_exchange_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('encounter_id')->nullable();
            $table->string('destination', 40);
            $table->string('event_type', 80);
            $table->char('idempotency_key', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->longText('payload');
            $table->longText('response_payload')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('remote_tracking_id', 120)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
        });
    }

    public function test_stale_processing_message_is_retried_with_its_original_idempotency_key(): void
    {
        $key = hash('sha256', 'same-message');
        $id = DB::table('health_exchange_messages')->insertGetId([
            'doctor_id' => 4,
            'destination' => 'sepas',
            'event_type' => 'encounter.signed',
            'idempotency_key' => $key,
            'status' => HealthExchangeMessage::PROCESSING,
            'payload' => '{}',
            'attempts' => 2,
            'available_at' => now()->subHour(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $gateway = Mockery::mock(HealthExchangeGateway::class);
        $gateway->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(fn (HealthExchangeMessage $message): bool =>
                $message->id === $id && $message->idempotency_key === $key
            ))
            ->andReturn(['tracking_id' => 'remote-123', 'response' => ['accepted' => true]]);
        $this->app->instance(HealthExchangeGateway::class, $gateway);

        Artisan::call('health:dispatch-exchange');

        $this->assertDatabaseHas('health_exchange_messages', [
            'id' => $id,
            'status' => HealthExchangeMessage::SENT,
            'idempotency_key' => $key,
            'remote_tracking_id' => 'remote-123',
            'attempts' => 3,
        ]);
    }

    public function test_recently_claimed_processing_message_is_not_dispatched_again(): void
    {
        $id = DB::table('health_exchange_messages')->insertGetId([
            'doctor_id' => 4,
            'destination' => 'sepas',
            'event_type' => 'appointment.booked',
            'idempotency_key' => hash('sha256', 'active-message'),
            'status' => HealthExchangeMessage::PROCESSING,
            'payload' => '{}',
            'attempts' => 1,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gateway = Mockery::mock(HealthExchangeGateway::class);
        $gateway->shouldNotReceive('dispatch');
        $this->app->instance(HealthExchangeGateway::class, $gateway);

        Artisan::call('health:dispatch-exchange');

        $this->assertDatabaseHas('health_exchange_messages', [
            'id' => $id,
            'status' => HealthExchangeMessage::PROCESSING,
            'attempts' => 1,
        ]);
    }
}
