<?php
require_once __DIR__ . '/Member.php';

class StudentMember extends Member
{
    public function borrowLimit()
    {
        return 3;
    }
    public function loanDays()
    {
        return 14;
    }
    public function lateFeePerDay()
    {
        return 0.50;
    }
}
