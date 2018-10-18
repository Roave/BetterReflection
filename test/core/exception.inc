<?php

declare(strict_types=1);

class ReflectionExceptionEx extends ReflectionException
{
    function MyException($_errno, $_errmsg) : void
    {
            $this->errno  = $_errno;
            $this->errmsg = $_errmsg;
    }

    function getErrno()
    {
            return $this->errno;
    }

    function getErrmsg()
    {
            return $this->errmsg;
    }
}
