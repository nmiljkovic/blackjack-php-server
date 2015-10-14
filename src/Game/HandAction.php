<?php

namespace Blackjack\Game;

class HandAction
{
    const ACTION_HIT = 'hit';
    const ACTION_STAND = 'stand';
    const ACTION_DOUBLE_DOWN = 'double_down';
    const ACTION_SPLIT = 'split';
    const ACTION_SURRENDER = 'surrender';

    /** @var string */
    private $action;

    /**
     * PlayerActionMessage constructor.
     *
     * @param string $action
     */
    public function __construct($action)
    {
        if (!in_array($action, [self::ACTION_HIT, self::ACTION_STAND, self::ACTION_DOUBLE_DOWN, self::ACTION_SPLIT, self::ACTION_SURRENDER], true)) {
            throw new \InvalidArgumentException('Invalid action, received '.$action);
        }
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    public function isHit()
    {
        return $this->action === self::ACTION_HIT;
    }

    public function isStand()
    {
        return $this->action === self::ACTION_STAND;
    }

    public function isDoubleDown()
    {
        return $this->action === self::ACTION_DOUBLE_DOWN;
    }

    public function isSplit()
    {
        return $this->action === self::ACTION_SPLIT;
    }

    public function isSurrender()
    {
        return $this->action === self::ACTION_SURRENDER;
    }
}
