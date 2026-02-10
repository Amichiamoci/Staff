<?php

namespace Amichiamoci\Models;

class Cron
{
    public string $Name;
    public string $FunctionName;

    public \DateTime $LastRun;

    public \DateInterval $Interval;

    public function __construct(
        string $name, 
        string $functionName, 
        \DateTime $lastRun, 
        \DateInterval $interval,
    ) {
        $this->Name = $name;
        $this->FunctionName = $functionName;
        $this->LastRun = $lastRun;
        $this->Interval = $interval;
    }

    public function isDue(): bool
    {
        $nextRun = clone $this->LastRun;
        $nextRun->add(interval: $this->Interval);
        $currentTime = new \DateTime();
        return $currentTime >= $nextRun;
    }

    public static function fetchFromDb(\mysqli $connection, string $name): ?self
    {
        $result = $connection->execute_query(
            query: "SELECT `function_name`, `last_run`, `interval_hours` FROM `cron` WHERE `name` = ?",
            params: [$name]
        );
        if (!$result || $result->num_rows === 0) 
            return null;

        $row = $result->fetch_assoc();
        $functionName = $row['function_name'];
        $lastRun = new \DateTime(datetime: $row['last_run']);
        $interval = new \DateInterval(duration: 'PT' . $row['interval_hours'] . 'H');
        return new self(
            name: $name, 
            functionName: $functionName, 
            lastRun: $lastRun, 
            interval: $interval
        );
    }

    public static function fetchAllFromDb(\mysqli $connection): array
    {
        $result = $connection->execute_query(
            query: "SELECT `name`, `function_name`, `last_run`, `interval_hours` FROM `cron`"
        );
        if (!$result) 
            return [];

        $cronJobs = [];
        while ($row = $result->fetch_assoc()) {
            $cronJobs[] = new self(
                name: $row['name'], 
                functionName: $row['function_name'], 
                lastRun: new \DateTime(datetime: $row['last_run']), 
                interval: new \DateInterval(duration: 'PT' . $row['interval_hours'] . 'H')
            );
        }
        return $cronJobs;
    }

    public function createInDB(\mysqli $connection): void
    {
        $connection->execute_query(
            query: "INSERT INTO `cron` (`name`, `function_name`, `interval_hours`) VALUES (?, ?, ?)",
            params: [
                $this->Name,
                $this->FunctionName,
                (int)$this->Interval->format(format: 'H'),
            ]
        );
    }

    public function updateLastRunInDb(\mysqli $connection): void
    {
        $connection->execute_query(
            query: "UPDATE `cron` SET `last_run` = ? WHERE `name` = ?",
            params: [
                $this->LastRun->format(format: 'Y-m-d H:i:s'),
                $this->Name,
            ]
        );
    }
}