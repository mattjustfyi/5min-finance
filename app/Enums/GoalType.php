<?php

namespace App\Enums;

enum GoalType: string
{
    case EMERGENCY_FUND = 'emergency_fund';
    case SAVINGS_GOAL = 'savings_goal';
}