<?php

namespace Tests;

use Mohammadhprp\IPToCountryFlagColumn\IPToCountryFlagColumnServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [IPToCountryFlagColumnServiceProvider::class];
    }
}
