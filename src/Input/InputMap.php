<?php

namespace ScrapyardIO\GameEngine\Input;

/**
 * Godot-like action map: action name → list of key/button matchers.
 */
class InputMap
{
    /**
     * @var array<string, list<array{type: string, value: string}>>
     */
    protected array $actions = [];

    public function addAction(string $action): static
    {
        $this->actions[$action] ??= [];

        return $this;
    }

    public function actionAddKey(string $action, string $key): static
    {
        $this->addAction($action);
        $this->actions[$action][] = ['type' => 'key', 'value' => $key];

        return $this;
    }

    public function actionAddMouseButton(string $action, string $button): static
    {
        $this->addAction($action);
        $this->actions[$action][] = ['type' => 'mouse', 'value' => $button];

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getActions(): array
    {
        return array_keys($this->actions);
    }

    /**
     * @return list<array{type: string, value: string}>
     */
    public function actionGetEvents(string $action): array
    {
        return $this->actions[$action] ?? [];
    }

    public function hasAction(string $action): bool
    {
        return array_key_exists($action, $this->actions);
    }

    /**
     * @return list<string>
     */
    public function actionsForKey(string $key): array
    {
        $matched = [];

        foreach ($this->actions as $action => $events) {
            foreach ($events as $event) {
                if ($event['type'] === 'key' && $event['value'] === $key) {
                    $matched[] = $action;
                }
            }
        }

        return $matched;
    }

    /**
     * @return list<string>
     */
    public function actionsForMouseButton(string $button): array
    {
        $matched = [];

        foreach ($this->actions as $action => $events) {
            foreach ($events as $event) {
                if ($event['type'] === 'mouse' && $event['value'] === $button) {
                    $matched[] = $action;
                }
            }
        }

        return $matched;
    }
}
