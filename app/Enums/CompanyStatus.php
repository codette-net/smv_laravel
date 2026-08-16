<?php
namespace App\Enums;

enum CompanyStatus: string
{
 case Draft = 'draft';
 case Pending = 'pending';
 case Active = 'active';
  case Archived = 'archived';
}

