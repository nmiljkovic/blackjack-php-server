<?php

namespace Blackjack\Server\Message\Client;

class PlayerSetNameMessage
{
    /** @var string */
    private $newName;

    public function __construct($newName)
    {
        $this->newName = $newName;
    }

    /**
     * @return string
     */
    public function getNewName()
    {
        return $this->newName;
    }

    public function isValid()
    {
        return preg_match('/^[\w0-9_]+$/', $this->getNewName()) === 1;
    }
}
