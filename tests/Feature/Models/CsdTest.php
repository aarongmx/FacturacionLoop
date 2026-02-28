<?php

declare(strict_types=1);

use App\Builders\CsdBuilder;
use App\Enums\CsdStatus;
use App\Models\Csd;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Crypt;

it('can be created via factory', function (): void {
    $csd = Csd::factory()->create();
    expect($csd)->toBeInstanceOf(Csd::class)
        ->and($csd->id)->toBeInt()
        ->and($csd->id)->toBeGreaterThan(0);
});

it('uses auto-increment integer primary key', function (): void {
    $csd = new Csd;
    expect($csd->incrementing)->toBeTrue()
        ->and($csd->getKeyName())->toBe('id');
});

it('encrypts passphrase on save and decrypts on read', function (): void {
    $plainPassphrase = 'mi-contraseña-secreta-2024';
    $csd = Csd::factory()->create([
        'passphrase_encrypted' => $plainPassphrase,
    ]);

    // Read back from database — 'encrypted' cast should decrypt automatically
    $fresh = Csd::find($csd->id);
    expect($fresh->passphrase_encrypted)->toBe($plainPassphrase);

    // Verify the raw database value is NOT the plaintext
    $rawValue = DB::table('csds')->where('id', $csd->id)->value('passphrase_encrypted');
    expect($rawValue)->not->toBe($plainPassphrase);

    // Verify the raw value can be decrypted by Laravel Crypt
    expect(Crypt::decryptString($rawValue))->toBe($plainPassphrase);
});

it('casts status to CsdStatus enum', function (): void {
    $csd = Csd::factory()->active()->create();
    expect($csd->status)->toBeInstanceOf(CsdStatus::class)
        ->and($csd->status)->toBe(CsdStatus::Active);
});

it('casts date fields correctly', function (): void {
    $csd = Csd::factory()->create([
        'fecha_inicio' => '2024-01-15',
        'fecha_fin' => '2028-01-15',
    ]);
    expect($csd->fecha_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($csd->fecha_fin)->toBeInstanceOf(CarbonInterface::class);
});

it('supports soft delete', function (): void {
    $csd = Csd::factory()->create();
    $id = $csd->id;

    $csd->delete();

    expect(Csd::find($id))->toBeNull()
        ->and(Csd::withTrashed()->find($id))->not->toBeNull();
});

it('creates active CSD with factory state', function (): void {
    $csd = Csd::factory()->active()->create();
    expect($csd->status)->toBe(CsdStatus::Active);
});

it('creates expiring soon CSD with factory state', function (): void {
    $csd = Csd::factory()->expiringSoon()->create();
    expect($csd->status)->toBe(CsdStatus::ExpiringSoon)
        ->and($csd->fecha_fin->diffInDays(now()))->toBeLessThanOrEqual(90);
});

it('creates expired CSD with factory state', function (): void {
    $csd = Csd::factory()->expired()->create();
    expect($csd->status)->toBe(CsdStatus::Expired)
        ->and($csd->fecha_fin->isPast())->toBeTrue();
});

it('uses CsdBuilder as custom query builder', function (): void {
    expect(Csd::query())->toBeInstanceOf(CsdBuilder::class);
});

it('filters active CSDs with whereActive', function (): void {
    Csd::factory()->active()->create();
    Csd::factory()->create(['status' => CsdStatus::Inactive]);
    Csd::factory()->expired()->create();

    $active = Csd::query()->whereActive()->get();
    expect($active)->toHaveCount(1)
        ->and($active->first()->status)->toBe(CsdStatus::Active);
});

it('filters expiring CSDs with whereExpiring', function (): void {
    Csd::factory()->active()->create(['fecha_fin' => now()->addDays(30)]);
    Csd::factory()->active()->create(['fecha_fin' => now()->addYears(3)]);
    Csd::factory()->expiringSoon()->create();
    Csd::factory()->expired()->create();

    $expiring = Csd::query()->whereExpiring()->get();
    // Should include: the active one expiring in 30 days + the expiringSoon one
    expect($expiring->count())->toBeGreaterThanOrEqual(2);
});

it('filters non-expired CSDs with whereNotExpired', function (): void {
    Csd::factory()->active()->create();
    Csd::factory()->expired()->create();

    $notExpired = Csd::query()->whereNotExpired()->get();
    expect($notExpired)->toHaveCount(1);
});
