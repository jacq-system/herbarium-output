<?php declare(strict_types=1);

namespace App\Service\Output;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SearchFormSessionService
{
    public const string SESSION_FILTERS = 'searchFormFilters';
    public const string SESSION_SETTINGS = 'searchFormSettings';
    public const string SESSION_SORT = 'searchFormSort';

    private ?SessionInterface $session = null;

    public function __construct(private readonly RequestStack $requestStack)
    {}

    protected function getSession(): SessionInterface
    {
        if (null === $this->session) {
            $request = $this->requestStack->getCurrentRequest();

            if ($request && $request->hasSession()) {
                $this->session = $request->getSession();
            }
        }

        return $this->session;
    }

    public function hasFilters(): bool
    {
        if ($this->getSession()->get(self::SESSION_FILTERS) === null) {
            return false;
        }
        return true;
    }

    public function getFilter(string $key, $default = null)
    {
        $filters = $this->getSession()->get(self::SESSION_FILTERS);
        if (isset($filters[$key])) {
            return $filters[$key];
        }
        return $default;
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->getSession()->get(self::SESSION_SETTINGS);
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        return $default;
    }

    public function setSetting(string $key, $value): static
    {
        $settings = $this->getSession()->get(self::SESSION_SETTINGS);
        $settings[$key] = $value;
        $this->setSettings($settings);
        return $this;
    }

    public function setSettings($formData): static
    {
        $this->getSession()->set(self::SESSION_SETTINGS, $formData);
        return $this;
    }

    public function setSort($formData): static
    {

        $actualSort = $this->getSort();
        if ($actualSort === null || key($actualSort) !== $formData) {
            $this->getSession()->set(self::SESSION_SORT, [$formData => 'ASC']);
        } else {
            if ($actualSort[$formData] === 'ASC') {
                $this->getSession()->set(self::SESSION_SORT, [$formData => 'DESC']);
            } else {
                $this->getSession()->remove(self::SESSION_SORT);
            }
        }

        return $this;
    }

    public function getSort()
    {
        $sort = $this->getSession()->get(self::SESSION_SORT);
        if (isset($sort)) {
            return $sort;
        }
        return null;
    }

    public function isSortedBy(string $sort): bool
    {
        if ($this->hasSort() !== false && key($this->getSort()) === $sort) {
            return true;
        }
        return false;
    }

    public function hasSort(): bool
    {
        if ($this->getSession()->get(self::SESSION_SORT) === null) {
            return false;
        }
        return true;
    }

    public function reset(): static
    {
        $this->getSession()->remove(self::SESSION_FILTERS);
        $this->getSession()->remove(self::SESSION_SETTINGS);
        $this->getSession()->remove(self::SESSION_SORT);
        return $this;
    }

    public function setFilters($formData): static
    {
        $this->getSession()->set(self::SESSION_FILTERS, $formData);
        return $this;
    }

    public function all(): array
    {
        return $this->getSession()->all();
    }
}
