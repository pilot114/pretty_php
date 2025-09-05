<?php

namespace PrettyPhp\Binary;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Binary
{
    // TODO: заменить format на длину в битах
    // TODO: сделать вложенность (ничего делать не надо, кроме как pack вручную?)
    public function __construct(public string $format)
    {
    }
}
