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

    /**
     * Checks if the CRON is (or was) due now with a confidence of $expiryMinutes
     * @param int $expiryMinutes Number of minutes to consider a CRON as "due" BEFORE its scheduled time. Default is 4 minutes.
     * @return bool true if the CRON is due, false otherwise
     */
    public function isDue(int $expiryMinutes = 4): bool
    {
        $nextRun = clone $this->LastRun;
        $nextRun->add(interval: $this->Interval);
        $nextRun->sub(interval: new \DateInterval(duration: 'PT' . $expiryMinutes . 'M'));

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

    private static function dateIntervalToHours(\DateInterval $interval): int
    {
        return ($interval->d * 24) + $interval->h + (int)($interval->i / 60);
    }

    public function createInDB(\mysqli $connection): void
    {
        $connection->execute_query(
            query: "INSERT INTO `cron` (`name`, `function_name`, `interval_hours`) VALUES (?, ?, ?)",
            params: [
                $this->Name,
                $this->FunctionName,
                self::dateIntervalToHours(interval: $this->Interval),
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