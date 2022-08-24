<?php

namespace dwy\FacebookConversion\twig;

use dwy\FacebookConversion\twig\functions\EventFunction;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getName(): string
    {
        return 'Facebook Conversion';
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fbEvent', new EventFunction()),
        ];
    }

    public function getOperators(): array
    {
        return [];
    }
}
