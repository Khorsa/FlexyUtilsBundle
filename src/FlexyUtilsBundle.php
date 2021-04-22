<?php
namespace flexycms\FlexyUtilsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FlexyUtilsBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}