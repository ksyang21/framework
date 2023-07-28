<?php

namespace utils;

class Middleware
{
	public function userAccess()
	{
        return TRUE;
	}

	public function adminAccess()
	{
		// Add your logic for admin middleware here
		// This will be executed when auth('admin') is called in the router
        return TRUE;
	}
}