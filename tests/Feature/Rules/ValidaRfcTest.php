<?php

declare(strict_types=1);

use App\Rules\ValidaRfc;
use Illuminate\Support\Facades\Validator;

it('accepts valid persona física RFC (13 chars)', function (string $rfc): void {
    $validator = Validator::make(
        ['rfc' => $rfc],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
})->with([
    'standard' => ['ABCD010101AB1'],
    'with numbers in homoclave' => ['XYZW890101123'],
    'with Ñ' => ['ÑABC010101AB1'],
    'with &' => ['&ABC010101AB1'],
]);

it('accepts valid persona moral RFC (12 chars)', function (string $rfc): void {
    $validator = Validator::make(
        ['rfc' => $rfc],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
})->with([
    'standard' => ['AAA010101AA1'],
    'alphanumeric homoclave' => ['ABC010101123'],
    'with Ñ' => ['ÑAB010101AB1'],
]);

it('accepts generic RFC XAXX010101000 (público en general)', function (): void {
    $validator = Validator::make(
        ['rfc' => 'XAXX010101000'],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
});

it('accepts generic RFC XEXX010101000 (extranjero)', function (): void {
    $validator = Validator::make(
        ['rfc' => 'XEXX010101000'],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
});

it('accepts lowercase input by normalizing to uppercase', function (): void {
    $validator = Validator::make(
        ['rfc' => 'abcd010101ab1'],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
});

it('accepts generic RFCs in lowercase', function (): void {
    $validator = Validator::make(
        ['rfc' => 'xaxx010101000'],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
});

it('trims whitespace before validation', function (): void {
    $validator = Validator::make(
        ['rfc' => '  ABCD010101AB1  '],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid RFC formats', function (string $rfc): void {
    $validator = Validator::make(
        ['rfc' => $rfc],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->fails())->toBeTrue();
})->with([
    'too short' => ['ABCD0101'],
    'too long' => ['ABCDE010101AB12'],
    'only letters' => ['ABCDEFGHIJKLM'],
    'only numbers' => ['1234567890123'],
    'special chars' => ['ABC@010101AB1'],
    'wrong date position' => ['ABCD01AB01011'],
]);

it('does not fail on empty string because Laravel skips non-implicit rules for absent values', function (): void {
    // Empty string is treated as absent — ValidaRfc is not an ImplicitRule.
    // Use required alongside ValidaRfc to enforce presence.
    $validator = Validator::make(
        ['rfc' => ''],
        ['rfc' => ['required', new ValidaRfc]],
    );
    expect($validator->fails())->toBeTrue();
});

it('returns Spanish error message on failure', function (): void {
    $validator = Validator::make(
        ['rfc' => 'INVALID'],
        ['rfc' => [new ValidaRfc]],
    );
    expect($validator->errors()->first('rfc'))
        ->toContain('formato válido');
});
