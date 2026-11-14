<?php

declare(strict_types=1);

namespace Robole\SuluAITranslatorBundle\Tests;

use PHPUnit\Framework\TestCase;
use Robole\SuluAITranslatorBundle\Admin\AITranslatorAdmin;
use Robole\SuluAITranslatorBundle\Service\TranslationPermissionChecker;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class AdminSettingsPageTest extends TestCase
{
    public function testSettingsPageIsShownByDefault(): void
    {
        [$navigation, $views] = $this->configure($this->admin());

        self::assertCount(1, $navigation->get(Admin::SETTINGS_NAVIGATION_ITEM)->getChildren() ?? []);
        self::assertTrue($views->has(AITranslatorAdmin::TRANSLATION_CONFIG_VIEW));
    }

    public function testSettingsPageCanBeSwitchedOff(): void
    {
        [$navigation, $views] = $this->configure($this->admin(false));

        self::assertCount(0, $navigation->get(Admin::SETTINGS_NAVIGATION_ITEM)->getChildren() ?? []);
        self::assertFalse($views->has(AITranslatorAdmin::TRANSLATION_CONFIG_VIEW));
    }

    public function testPermissionStaysWhenSettingsPageIsOff(): void
    {
        $contexts = $this->admin(false)->getSecurityContexts();

        self::assertArrayHasKey(AITranslatorAdmin::SECURITY_CONTEXT, $contexts[Admin::SULU_ADMIN_SECURITY_SYSTEM]['AI Translator']);
    }

    private function admin(bool $settingsPage = true): AITranslatorAdmin
    {
        $securityChecker = $this->createMock(SecurityCheckerInterface::class);
        $securityChecker->method('hasPermission')->willReturn(true);

        return new AITranslatorAdmin(
            new ViewBuilderFactory(),
            $securityChecker,
            new TranslationPermissionChecker($securityChecker),
            settingsPage: $settingsPage
        );
    }

    /**
     * @return array{NavigationItemCollection, ViewCollection}
     */
    private function configure(AITranslatorAdmin $admin): array
    {
        $navigation = new NavigationItemCollection();
        $navigation->add(new NavigationItem('sulu_admin.settings'));
        $views = new ViewCollection();

        $admin->configureNavigationItems($navigation);
        $admin->configureViews($views);

        return [$navigation, $views];
    }
}
