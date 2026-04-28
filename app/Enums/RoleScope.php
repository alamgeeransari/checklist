<?php

namespace App\Enums;

enum RoleScope: string
{
    case GLOBAL = 'global';
    case COMPANY = 'company';
    case PROJECT = 'project';
}
