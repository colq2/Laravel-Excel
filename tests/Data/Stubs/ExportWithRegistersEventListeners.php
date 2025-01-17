<?php

namespace Maatwebsite\Excel\Tests\Data\Stubs;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;

class ExportWithRegistersEventListeners implements WithEvents
{
    use Exportable, RegistersEventListeners;

    /**
     * @var callable
     */
    public static $beforeExport;

    /**
     * @var callable
     */
    public static $beforeWriting;

    /**
     * @var callable
     */
    public static $beforeSheet;

    /**
     * @var callable
     */
    public static $afterSheet;

    /**
     * @var callable
     */
    public static $beforeChunk;

    /**
     * @var callable
     */
    public static $afterChunk;

    public static function beforeExport()
    {
        (static::$beforeExport)(...func_get_args());
    }

    public static function beforeWriting()
    {
        (static::$beforeWriting)(...func_get_args());
    }

    public static function beforeSheet()
    {
        (static::$beforeSheet)(...func_get_args());
    }

    public static function afterSheet()
    {
        (static::$afterSheet)(...func_get_args());
    }

    public static function beforeChunk()
    {
        (static::$beforeChunk)(...func_get_args());
    }

    public static function afterChunk()
    {
        (static::$afterChunk)(...func_get_args());
    }
}