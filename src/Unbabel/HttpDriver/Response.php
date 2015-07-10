<?php

namespace Unbabel\HttpDriver;

interface Response
{
    /**
     * return JSON-decoded response
     * @return array
     */
    public function json();

    /**
     * Return raw response
     * @return string
     */
    public function raw();

    /**
     * @return int
     */
    public function getStatusCode();
}