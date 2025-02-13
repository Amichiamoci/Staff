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
        
        if (empty($receiver) || !filter_var(value: $receiver, filter: FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(message: "'$receiver' is not a valid email!");
        }
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

    public static function All(\mysqli $connection) : array
    {
        if (!$connection)
            return [];
        
        $result = $connection->query(query: "SELECT * FROM `email_extended_no_body` LIMIT 2000");
        if (!$result) {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                id: $row["id"],
                receiver: $row["destinatario"],
                sender: array_key_exists(key: "invio_da", array: $row) ? $row["invio_da"] : null,
                subject: $row["oggetto"],
                sent: $row["inviata"],
                content: array_key_exists(key: "testo", array: $row) ? $row["testo"] : null,
                opened: $row["aperta"],
                received: $row["ricevuta"],
            );
        }
        $result->close();
        return $arr;
    }

    public static function ById(\mysqli $connection, int $id): ?Email
    {
        if (!$connection)
            return null;
        
        $result = $connection->query(query: "CALL ViewEmail($id);");
        if (!$result) {
            return null;
        }

        $obj = null;
        if ($row = $result->fetch_assoc())
        {
            $obj = new self(
                id: $row["id"],
                receiver: $row["destinatario"],
                sender: array_key_exists(key: "invio_da", array: $row) ? $row["invio_da"] : null,
                subject: $row["oggetto"],
                sent: $row["inviata"],
                content: array_key_exists(key: "testo", array: $row) ? $row["testo"] : null,
                opened: $row["aperta"],
                received: $row["ricevuta"],
            );
        }
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