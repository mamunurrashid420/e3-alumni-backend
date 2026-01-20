<?php

namespace App\Enums;

enum PaymentPurpose: string
{
    case AssociateMembershipFees = 'ASSOCIATE_MEMBERSHIP_FEES';
    case GeneralMembershipFees = 'GENERAL_MEMBERSHIP_FEES';
    case LifetimeMembershipFees = 'LIFETIME_MEMBERSHIP_FEES';
    case SpecialYearlyContributionExecutive = 'SPECIAL_YEARLY_CONTRIBUTION_EXECUTIVE';
    case Donations = 'DONATIONS';
    case Patron = 'PATRON';
    case Others = 'OTHERS';
}
