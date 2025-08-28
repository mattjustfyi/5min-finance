<?php

namespace App\Enums;

enum DebtType: string
{
    case MONEY_OWED = 'money_owed';
    case MONEY_OWING = 'money_owing';
}