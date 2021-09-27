<?php

if (class_exists(ReflectionIntersectionType::class, false)) {
    return;
}

class ReflectionIntersectionType extends ReflectionType
{
    /** @return ReflectionType[] */
    public function getTypes()
    {
        return [];
    }
}
