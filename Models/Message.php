<?php

namespace Amichiamoci\Models;

class Message
{
    public MessageType $Type;
    public string $Content;

    public function __construct(
        MessageType|string $type,
        string $content,
    ) {
        $this->Content = $content;
        if (is_string(value: $type)) {
            $this->Type = MessageType::from(value: $type);
        } else {
            $this->Type = $type;
        }
    }

    public function Icon(): string { return $this->Type->Icon(); }
}