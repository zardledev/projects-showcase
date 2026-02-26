<?php

namespace App\Core\Config;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigurationService
{
    public function __construct(private ParameterBagInterface $parameters) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->parameters->has($key)) {
            return $this->parameters->get($key);
        }

        return $default;
    }
}
