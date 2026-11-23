<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\DbEntity;

class Email implements DbEntity
{
    public int $Id;
    public string $Receiver;
    public ?string $Sender = null;
    public string $Subject;
    public string $Sent;
    public ?string $Content = null;
    public ?string $Opened = null;
    public bool $Received = true;

    public function __construct(
        int|string $id,
        string $receiver,
        ?string $sender,
        string $subject,
        string $sent,
        ?string $content = null,
        ?string $opened = null,
        int|string|bool $received = false
    ) {
        $this->Id = (int)$id;
        
        $this->Receiver = $receiver;

        if (!empty($sender) && filter_var(value: $sender, filter: FILTER_VALIDATE_EMAIL)) {
            $this->Sender = $sender;
        }

        $this->Subject = $subject;
        $this->Sent = $sent;
        $this->Content = $content;
        $this->Opened = $opened;
        $this->Received = is_string(value: $received) ? 
            (strtolower(string: $received) === 'true' || $received == '1') :
            ((bool)$received);
    }

    public static function All(\mysqli $connection, ?string $recipient = null): array
    {
        $query = "SELECT id, dest AS destinatario, COALESCE(subject, '') AS oggetto, ricevuta,
                         DATE_FORMAT(sent, '%d/%m/%Y %H:%i') AS inviata,
                         DATE_FORMAT(opened, '%d/%m/%Y %H:%i') AS aperta
                  FROM email";
        $order = ' ORDER BY sent DESC, id DESC';
        $result = $recipient === null
            ? $connection->query($query . $order . ' LIMIT 500')
            : $connection->execute_query($query . ' WHERE dest = ?' . $order, [$recipient]);

        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[] = self::FromRow($row);
        }
        $result->close();
        return $emails;
    }

    public static function Recipients(\mysqli $connection): array
    {
        $result = $connection->query("SELECT DISTINCT dest FROM email WHERE dest <> '' ORDER BY dest");
        $recipients = array_column($result->fetch_all(MYSQLI_NUM), 0);
        $result->close();
        return $recipients;
    }

    private static function FromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            receiver: $row['destinatario'],
            sender: $row['invio_da'] ?? null,
            subject: $row['oggetto'],
            sent: $row['inviata'],
            content: $row['testo'] ?? null,
            opened: $row['aperta'],
            received: $row['ricevuta'],
        );
    }

    public static function ById(\mysqli $connection, int $id): ?Email
    {
        if (!$connection)
            return null;
        
        $result = $connection->query(query: "CALL ViewEmail($id);");
        if (!$result) {
            return null;
        }

        $row = $result->fetch_assoc();
        $obj = $row === null ? null : self::FromRow($row);
        $result->close();
        $connection->next_result();
        return $obj;
    }

    public static function HeartBeat(\mysqli $connection, int $id): bool {
        if (!$connection)
            return false;
        $result = $connection->query(query: "CALL OpenedEmail($id);");
        return (bool)$result;
    }
}