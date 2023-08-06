<?php

namespace app;

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