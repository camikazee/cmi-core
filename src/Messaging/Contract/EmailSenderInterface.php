<?php

declare(strict_types=1);

namespace Core\Messaging\Contract;

use Symfony\Component\Mime\Email;

interface EmailSenderInterface
{
    public function send(Email $email): void;
}

