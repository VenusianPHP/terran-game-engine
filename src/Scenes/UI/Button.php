<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\Contracts\HumanInput\MouseButton;
use Terran\Input\Events\ActionPressed;
use Terran\Input\Events\InputEvent;
use Terran\Input\Events\MouseButtonPressed;
use Terran\Input\Events\MouseButtonReleased;
use Terran\Math\Vector2;
use Terran\Scenes\ResolvesFont;

/**
 * Emits pressed on a left release inside it after a left press inside it, or
 * on ui_accept while focused. A disabled button ignores both. A subclass that
 * overrides input() calls parent::input().
 */
#[\Terran\Interrupts\Interrupt('pressed')]
class Button extends UIControl
{
    use ResolvesFont;

    public int $size = 1;

    public ButtonColors $colors;

    public bool $disabled = false;

    private bool $hover = false;

    private bool $down = false;

    public function __construct(string $name, public string $text = '')
    {
        parent::__construct($name);
        $this->colors = ButtonColors::defaults();
        $this->focusable = true;
    }

    public function hovered(): bool { return $this->hover; }

    public function down(): bool { return $this->down; }

    public function input(InputEvent $event): bool
    {
        if ($event instanceof MouseButtonPressed && $event->button === MouseButton::LEFT) {
            if (! $this->disabled && $this->hitTest($event->x, $event->y, $event->viewport)) {
                $this->down = true;
            }

            return true;
        }
        if ($event instanceof MouseButtonReleased && $event->button === MouseButton::LEFT) {
            $was_down = $this->down;
            $this->down = false;
            if ($was_down && ! $this->disabled && $this->hitTest($event->x, $event->y, $event->viewport)) {
                $this->emit('pressed');
            }

            return true;
        }
        if ($event instanceof ActionPressed && $event->action === 'ui_accept' && ! $this->disabled && $this->hasFocus()) {
            $this->emit('pressed');

            return true;
        }

        return false;
    }

    public function mouseEntered(): void { $this->hover = true; }

    public function mouseExited(): void { $this->hover = false; }

    protected function contentMinSize(RenderingEngine $ge): Vector2
    {
        if ($this->text === '') {
            return new Vector2(16.0, 16.0);
        }
        [, , $w, $h] = $ge->textBounds($this->text, $this->face());

        return new Vector2($w * $this->size + 16.0, $h * $this->size + 8.0);
    }

    public function draw(RenderingEngine $ge): void
    {
        $size = $this->size();
        $fill = match (true) {
            $this->disabled => $this->colors->disabled,
            $this->down => $this->colors->pressed,
            $this->hover => $this->colors->hover,
            default => $this->colors->normal,
        };
        $ge->fillRect(0.0, 0.0, $size->x, $size->y, $this->tint($fill));
        if ($this->hasFocus()) {
            $ge->strokeRect(1.0, 1.0, $size->x - 2.0, $size->y - 2.0, $this->tint($this->colors->focus), 2.0);
        }
        if ($this->text !== '') {
            $face = $this->face();
            [$bx, $by, $bw, $bh] = $ge->textBounds($this->text, $face);
            $ge->translate(($size->x - $bw * $this->size) / 2, ($size->y - $bh * $this->size) / 2);
            $ge->scale((float) $this->size);
            $ge->text($this->text, -$bx, -$by, $this->tint($this->disabled ? $this->colors->disabledText : $this->colors->text), $face);
        }
    }
}
