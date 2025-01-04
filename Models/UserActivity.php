<?php

namespace Amichiamoci\Models;

use DateInterval;

class UserActivity
{
    public string $UserName;

    public ?\DateTime $Start = null;
    public ?\DateTime $LastHit = null;

    public string $Flag;
    public ?string $Ip;

    public function __construct(
        string $user_name,
        string|\DateTime|null $time_start,
        string|\DateTime|null $time_log,
        string $flag,
        ?string $ip,
    ) {
        $this->UserName = $user_name;
        $this->Flag = $flag;
        
        $this->Ip = $ip;
        if ($this->Ip === 'localhost' || $this->Ip === '::1') {
            $this->Ip = '127.0.0.1';
        }

        if (isset($time_start)) {
            if ($time_start instanceof \DateTime) {
                $this->Start = $time_start;
            } else {
                $this->Start = new \DateTime(datetime: $time_start);
            }
        }

        if (isset($time_log)) {
            if ($time_log instanceof \DateTime) {
                $this->LastHit = $time_log;
            } else {
                $this->LastHit = new \DateTime(datetime: $time_log);
            }
        }
    }

    public function Duration(): ?DateInterval
    {
        if (!isset($this->Start) || !isset($this->LastHit))
        {
            return null;
        }
        return date_diff(baseObject: $this->LastHit, targetObject: $this->Start);
    }
}