<?php

return new class()
{
    public function getWrapped(int $index)
    {
        if (0 === $index) {
            return new class() {};
        }

        return new class() {};
    }
};
