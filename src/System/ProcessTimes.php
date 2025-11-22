<?php

declare(strict_types=1);

namespace PrettyPhp\System;

/**
 * Value object representing process times
 */
readonly class ProcessTimes
{
    public function __construct(
        public int $ticks,
        public int $utime,
        public int $stime,
        public int $cutime,
        public int $cstime
    ) {
    }

    /**
     * Get process times
     * @throws \RuntimeException
     */
    public static function get(): self
    {
        $times = posix_times();
        if ($times === false) {
            throw new \RuntimeException("Failed to get process times");
        }

        /** @var int $ticks */
        $ticks = $times['ticks'];
        /** @var int $utime */
        $utime = $times['utime'];
        /** @var int $stime */
        $stime = $times['stime'];
        /** @var int $cutime */
        $cutime = $times['cutime'];
        /** @var int $cstime */
        $cstime = $times['cstime'];

        return new self($ticks, $utime, $stime, $cutime, $cstime);
    }

    /**
     * Get user time in seconds
     */
    public function getUserTime(): float
    {
        return $this->utime / $this->ticks;
    }

    /**
     * Get system time in seconds
     */
    public function getSystemTime(): float
    {
        return $this->stime / $this->ticks;
    }

    /**
     * Get children user time in seconds
     */
    public function getChildrenUserTime(): float
    {
        return $this->cutime / $this->ticks;
    }

    /**
     * Get children system time in seconds
     */
    public function getChildrenSystemTime(): float
    {
        return $this->cstime / $this->ticks;
    }

    /**
     * Get total time in seconds
     */
    public function getTotalTime(): float
    {
        return ($this->utime + $this->stime) / $this->ticks;
    }
}
