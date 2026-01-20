<?php
class Member
{
    protected int $id;
    protected string $type;
    protected string $name;
    protected string $email;
    protected ?string $phone;
    protected string $membershipStart;
    protected string $membershipEnd;
    protected float $unpaidBalance;

    public function __construct($id, $type, $name, $email, $phone, $start, $end, $balance)
    {
        $this->id = (int)$id;
        $this->type = $type;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone ? $phone : null;
        $this->membershipStart = $start;
        $this->membershipEnd = $end;
        $this->unpaidBalance = (float)$balance;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getType()
    {
        return $this->type;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getPhone()
    {
        return $this->phone;
    }
    public function getMembershipEnd()
    {
        return $this->membershipEnd;
    }
    public function getUnpaidBalance()
    {
        return $this->unpaidBalance;
    }

    
    public function borrowLimit()
    {
        return 0;
    }
    public function loanDays()
    {
        return 0;
    }
    public function lateFeePerDay()
    {
        return 0.0;
    }

    public function membershipValidToday(): bool
    {
        return strtotime(date('Y-m-d')) <= strtotime($this->membershipEnd);
    }
}
