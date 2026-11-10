<?php

namespace App\Exceptions;

use Exception;

/**
 * Нарушение бизнес-правила: отделяет предсказуемые ошибки клиента
 * (409, 422) от технических сбоев (500).
 */
class BusinessException extends Exception
{
    public function __construct(string $message, private readonly int $status = 409)
    {
        parent::__construct($message);
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }

    public static function unprocessable(string $message): self
    {
        return new self($message, 422);
    }

    public function status(): int
    {
        return $this->status;
    }
}
