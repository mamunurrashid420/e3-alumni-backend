<?php

namespace App\Services;

use App\Enums\PaymentPurpose;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MoneyReceiptService
{
    /**
     * Generate a money receipt PDF for the given payment.
     */
    public function generateReceipt(Payment $payment): string
    {
        // Get payment purpose label
        $paymentPurposeLabel = $this->getPaymentPurposeLabel($payment->payment_purpose);

        // Generate PDF
        $pdf = Pdf::loadView('receipts.money-receipt', [
            'payment' => $payment,
            'paymentPurposeLabel' => $paymentPurposeLabel,
        ]);

        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        // Generate filename
        $filename = 'money_receipt_'.Str::random(20).'.pdf';
        $path = 'receipts/'.$filename;

        // Save PDF to storage
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get the human-readable label for payment purpose.
     */
    private function getPaymentPurposeLabel(?PaymentPurpose $purpose): string
    {
        if (! $purpose) {
            return 'N/A';
        }

        return match ($purpose) {
            PaymentPurpose::AssociateMembershipFees => 'Associate Membership Fees',
            PaymentPurpose::GeneralMembershipFees => 'General Membership Fees',
            PaymentPurpose::LifetimeMembershipFees => 'Lifetime Membership Fees',
            PaymentPurpose::SpecialYearlyContributionExecutive => 'Special Yearly Contribution (Executive)',
            PaymentPurpose::YearlySubscriptionAssociateMember => 'Yearly Subscription (Associate Member)',
            PaymentPurpose::YearlySubscriptionGeneralMember => 'Yearly Subscription (General Member)',
            PaymentPurpose::YearlySubscriptionLifetimeMember => 'Yearly Subscription (Lifetime Member)',
            PaymentPurpose::Donations => 'Donations',
            PaymentPurpose::Patron => 'Patron',
            PaymentPurpose::Others => 'Others',
        };
    }
}
