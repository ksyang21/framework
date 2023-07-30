<?php

namespace controllers;

class TestController extends BaseController
{
    public function test(): void
    {
        $this->response['msg'] = 'Test';
        $this->respond();
    }
}