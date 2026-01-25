<?php

namespace App\Enums;

enum PaymentPurpose: string
{
    case AssociateMembershipFees = 'ASSOCIATE_MEMBERSHIP_FEES';
    case GeneralMembershipFees = 'GENERAL_MEMBERSHIP_FEES';
    case LifetimeMembershipFees = 'LIFETIME_MEMBERSHIP_FEES';
    case SpecialYearlyContributionExecutive = 'SPECIAL_YEARLY_CONTRIBUTION_EXECUTIVE';
    case YearlySubscriptionAssociateMember = 'YEARLY_SUBSCRIPTION_ASSOCIATE_MEMBER';
    case YearlySubscriptionGeneralMember = 'YEARLY_SUBSCRIPTION_GENERAL_MEMBER';
    case YearlySubscriptionLifetimeMember = 'YEARLY_SUBSCRIPTION_LIFETIME_MEMBER';
    case Donations = 'DONATIONS';
    case Patron = 'PATRON';
    case Others = 'OTHERS';
}
