<?php

namespace App\Enums;

enum StudentshipProofType: string
{
    case Jsc = 'JSC';
    case Eight = 'EIGHT';
    case Ssc = 'SSC';
    case MetricCertificate = 'METRIC_CERTIFICATE';
    case MarkSheet = 'MARK_SHEET';
    case Others = 'OTHERS';
}
