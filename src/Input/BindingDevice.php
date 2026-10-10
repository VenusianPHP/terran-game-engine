<?php
declare(strict_types=1);
namespace Terran\Input;

enum BindingDevice: string
{
    case KEY = 'key';
    case MOUSE = 'mouse';
    case PAD = 'pad';
    case AXIS = 'axis';
}
