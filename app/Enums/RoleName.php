<?php

namespace App\Enums;

enum RoleName: string
{
    case SUPER_ADMIN = 'Super Admin';
    case COMPANY_ADMIN = 'Company Admin';
    case MANAGER = 'Manager';
    case TECHNICAL_MANAGER = 'Technical Manager';
    case TEAM_LEAD = 'Team Lead';
    case TEAM_MEMBER = 'Team Member';
}
