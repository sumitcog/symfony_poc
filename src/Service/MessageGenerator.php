<?php
// src/Service/MessageGenerator.php
namespace App\Service;

class MessageGenerator
{
    public function getAccessDeniedHttpExceptionMessage(): string
    {
        $messages = [
            'Access denied, the user is not fully authenticated; redirecting to authentication entry point.',
        ];

        $index = array_rand($messages);

        return $messages[$index];
    }
}