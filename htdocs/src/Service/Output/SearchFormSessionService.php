<?php declare(strict_types=1);

namespace App\Service\Output;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class SearchFormSessionService
{
    public const string SESSION_FILTERS = 'searchFormFilters';
    public const string SESSION_SETTINGS = 'searchFormSettings';

    private ?SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function hasFilters(): bool
    {
        if ($this->session->get(self::SESSION_FILTERS) === null) {
            return false;
        }
        return true;
    }

    public function getFilter(string $key, $default = null)
    {
        $filters = $this->session->get(self::SESSION_FILTERS);
        if (isset($filters[$key])) {
            return $filters[$key];
        }
        return $default;
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->session->get(self::SESSION_SETTINGS);
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        return $default;
    }

    public function setSetting(string $key, $value): static
    {
        $settings = $this->session->get(self::SESSION_SETTINGS);
        $settings[$key] = $value;
        $this->setSettings($settings);
        return $this;
    }

    public function setSettings($formData): static
    {
        $this->session->set(self::SESSION_SETTINGS, $formData);
        return $this;
    }

    public function reset(): static
    {
        $this->session->remove(self::SESSION_FILTERS);
        $this->session->remove(self::SESSION_SETTINGS);
        return $this;
    }

    public function setFilters($formData): static
    {
        $this->session->set(self::SESSION_FILTERS, $formData);
        return $this;
    }

    public function all(): array
    {
        return $this->session->all();
    }
}
