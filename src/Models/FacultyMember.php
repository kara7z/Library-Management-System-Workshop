<?php
require_once __DIR__ . '/Member.php';

class FacultyMember extends Member
{
    public function borrowLimit()
    {
        return 10;
    }
    public function loanDays()
    {
        return 30;
    }
    public function lateFeePerDay()
    {
        return 0.25;
    }
}
