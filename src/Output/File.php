<?php

namespace Uiibevy\Flutzig\Output;

use Stringable;
use Uiibevy\Flutzig\Flutzig;

class File implements Stringable
{
    protected Flutzig $flutzig;

    public function __construct(Flutzig $flutzig)
    {
        $this->flutzig = $flutzig;
    }

    public function __toString(): string
    {
        return $this->flutzig->toJson();
    }
}
