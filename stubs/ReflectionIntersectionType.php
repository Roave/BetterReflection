<?php

if (class_exists(ReflectionIntersectionType::class, false)) {
    return;
}

class ReflectionIntersectionType extends ReflectionType
{
    /** @return non-empty-list<ReflectionType> */
    public function getTypes()
    {
        return [];
    }
}
