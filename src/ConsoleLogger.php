<?php

namespace Blackjack;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

class ConsoleLogger implements LoggerInterface
{
    const VERBOSITY_SILENT = 0;
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_VERY_VERBOSE = 2;
    const VERBOSITY_VERY_VERY_VERBOSE = 3;

    use LoggerTrait;

    private $format = '[%date%][%severity%] %message% %context%';

    private $dateFormat = 'H:i:s';

    private $verbosity = self::VERBOSITY_VERY_VERBOSE;

    /**
     * @return int
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @param int $verbosity
     */
    public function setVerbosity($verbosity)
    {
        $this->verbosity = $verbosity;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->verbosity < self::VERBOSITY_VERBOSE) {
            return;
        }

        if (in_array($level, [LogLevel::INFO, LogLevel::DEBUG, LogLevel::NOTICE], true) && $this->verbosity < self::VERBOSITY_VERY_VERBOSE) {
            return;
        }

        if ($level === LogLevel::DEBUG && $this->verbosity < self::VERBOSITY_VERY_VERY_VERBOSE) {
            return;
        }

        $line = str_replace([
            '%date%',
            '%severity%',
            '%message%',
            '%context%',
        ], [
            (new \DateTime())->format($this->dateFormat),
            strtoupper($level),
            trim($message),
            json_encode($context),
        ], $this->format);

        echo $line."\n";
    }
}
