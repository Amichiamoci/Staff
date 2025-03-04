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

    public static function Error(string $content): self
    {
        return new self(type: MessageType::Error, content: $content);
    }
    public static function Warn(string $content): self
    {
        return new self(type: MessageType::Warning, content: $content);
    }
    public static function Info(string $content): self
    {
        return new self(type: MessageType::Info, content: $content);
    }
    public static function Success(string $content): self
    {
        return new self(type: MessageType::Success, content: $content);
    }
}