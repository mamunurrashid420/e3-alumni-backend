<?php

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentApprovedSms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('sends sms to payee when payment is approved', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();
    $payment = Payment::create([
        'member_id' => null,
        'name' => 'Test Payee',
        'address' => 'Test Address',
        'mobile_number' => '01700000001',
        'payment_purpose' => PaymentPurpose::YearlySubscriptionGeneralMember,
        'payment_method' => 'BANK_TRANSFER',
        'payment_amount' => 500.00,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/payments/{$payment->id}/approve")
        ->assertSuccessful();

    Notification::assertSentTo($payment, PaymentApprovedSms::class);
});

it('does not send sms when payment has no mobile number', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();
    $payment = Payment::create([
        'member_id' => null,
        'name' => 'Test Payee',
        'address' => 'Test Address',
        'mobile_number' => '',
        'payment_purpose' => PaymentPurpose::Donations,
        'payment_method' => 'BANK_TRANSFER',
        'payment_amount' => 1000.00,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/payments/{$payment->id}/approve")
        ->assertSuccessful();

    Notification::assertNotSentTo($payment, PaymentApprovedSms::class);
});
