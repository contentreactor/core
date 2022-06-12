<?php

namespace Developion\Core\Models;

use craft\base\Model;

class ImageSizes extends Model
{
    public int|string|null $w1920 = null;
    public int|string|null $w1400 = null;
    public int|string|null $w1200 = null;
    public int|string|null $w992 = null;
    public int|string|null $w768 = null;
    public int|string|null $w576 = null;
    public int|string|null $w375 = null;
}
