<?php

namespace utils;

class Middleware
{
    public function userAccess(): bool
    {
        return TRUE;
    }

    public function adminAccess(): bool
    {
        return TRUE;
    }
}