<?php

declare(strict_types=1);

namespace Robole\SuluAITranslatorBundle\Dashboard;

use DeepL\AuthorizationException;
use DeepL\ConnectionException;
use DeepL\DeepLException;
use Manuxi\SuluDashboardBundle\Widget\DashboardWidgetInterface;
use Manuxi\SuluDashboardBundle\Widget\WidgetColumns;
use Manuxi\SuluDashboardBundle\Widget\WidgetTypes;
use Psr\Log\LoggerInterface;
use Robole\SuluAITranslatorBundle\Admin\AITranslatorAdmin;
use Robole\SuluAITranslatorBundle\Service\DeeplService;

/**
 * Tile for the dashboard of manuxi/sulu-dashboard-bundle: the DeepL character usage of the month as a bar.
 *
 * This class is only loaded when the dashboard bundle is installed (see SuluAITranslatorExtension), so the
 * dashboard stays an optional dependency ("suggest").
 *
 * Problems (no API key, key rejected, DeepL not reachable) are not thrown but returned as a readable text in the
 * tile, because the dashboard would only show a generic error for an exception.
 */
final class DeeplUsageWidget implements DashboardWidgetInterface
{
    public const KEY = 'deepl_usage';

    /**
     * @param \Closure(): DeeplService $deeplService lazy: DeeplService throws in its constructor without an API key
     */
    public function __construct(
        private readonly \Closure $deeplService,
        private readonly string $apiKey,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getTitle(): string
    {
        return 'app.translator_config_headline';
    }

    public function getType(): string
    {
        return WidgetTypes::PROGRESS;
    }

    public function getSecurityContext(): string
    {
        return AITranslatorAdmin::SECURITY_CONTEXT;
    }

    public function getColumn(): string
    {
        return WidgetColumns::SIDE;
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getData(string $locale): array
    {
        if ('' === trim($this->apiKey)) {
            return $this->problem('app.translator_dashboard_no_key');
        }

        try {
            $usage = ($this->deeplService)()->getUsage();
        } catch (AuthorizationException) {
            return $this->problem('app.translator_dashboard_key_rejected');
        } catch (ConnectionException $exception) {
            $this->logger?->warning('DeepL usage could not be loaded: {message}', ['message' => $exception->getMessage()]);

            return $this->problem('app.translator_dashboard_unreachable');
        } catch (DeepLException $exception) {
            $this->logger?->warning('DeepL usage could not be loaded: {message}', ['message' => $exception->getMessage()]);

            return $this->problem('app.translator_dashboard_failed');
        }

        if (null === $usage) {
            return $this->problem('app.translator_dashboard_no_usage');
        }

        $count = max(0, (int) $usage->character_count);
        $limit = max(0, (int) $usage->character_limit);
        $percent = $limit > 0 ? (int) round($count / $limit * 100) : 0;

        return [
            'text' => [
                'key' => 'app.translator_config_usage_statistics',
                'parameters' => [
                    'characterCount' => $this->format($count, $locale),
                    'characterLimit' => $this->format($limit, $locale),
                ],
            ],
            'display' => 'bar',
            'items' => [[
                'label' => 'app.translator_dashboard_characters',
                'value' => min($count, $limit),
                'max' => $limit,
                'text' => $percent . ' %',
                'skin' => $this->skin($percent),
                'note' => 'app.translator_dashboard_manage',
            ]],
        ];
    }

    private function skin(int $percent): string
    {
        if ($percent >= 100) {
            return 'error';
        }

        return $percent >= 80 ? 'warning' : 'progress';
    }

    /**
     * @return array<string, mixed>
     */
    private function problem(string $translationKey): array
    {
        return [
            'text' => $translationKey,
            'display' => 'bar',
            'items' => [],
        ];
    }

    private function format(int $value, string $locale): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatted = (new \NumberFormatter($locale, \NumberFormatter::DECIMAL))->format($value);

            if (false !== $formatted) {
                return $formatted;
            }
        }

        return number_format($value, 0, ',', '.');
    }
}
