<?php

declare(strict_types=1);

namespace Robole\SuluAITranslatorBundle\Tests;

use PHPUnit\Framework\TestCase;
use Robole\SuluAITranslatorBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testUsageStatisticsAreOnByDefault(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [['deepl_api_key' => 'key']]);

        self::assertTrue($config['usage_statistics']['settings_page']);
        self::assertTrue($config['usage_statistics']['dashboard']);
    }

    public function testBothSwitchesCanBeTurnedOffSeparately(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'usage_statistics' => ['settings_page' => false],
        ]]);

        self::assertFalse($config['usage_statistics']['settings_page']);
        self::assertTrue($config['usage_statistics']['dashboard']);
    }
}
