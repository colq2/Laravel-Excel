<?php

namespace Maatwebsite\Excel\Tests\Concerns;

use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeChunk;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Reader;
use Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Tests\Data\Stubs\AfterQueueExportJob;
use Maatwebsite\Excel\Tests\Data\Stubs\BeforeExportListener;
use Maatwebsite\Excel\Tests\Data\Stubs\Database\Group;
use Maatwebsite\Excel\Tests\Data\Stubs\Database\User;
use Maatwebsite\Excel\Tests\Data\Stubs\ExportWithEvents;
use Maatwebsite\Excel\Tests\Data\Stubs\ExportWithRegistersEventListeners;
use Maatwebsite\Excel\Tests\Data\Stubs\ImportWithRegistersEventListeners;
use Maatwebsite\Excel\Tests\Data\Stubs\QueuedExportWithChunkEvents;
use Maatwebsite\Excel\Tests\Data\Stubs\QueuedExportWithStaticEvents;
use Maatwebsite\Excel\Tests\TestCase;
use Maatwebsite\Excel\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistersEventListenersTest extends TestCase
{
    /**
     * @test
     */
    public function events_get_called_when_exporting()
    {
        $event = new ExportWithRegistersEventListeners();

        $eventsTriggered = 0;

        $event::$beforeExport = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(BeforeExport::class, $event);
            $this->assertInstanceOf(Writer::class, $event->writer);
            $eventsTriggered++;
        };

        $event::$beforeWriting = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(BeforeWriting::class, $event);
            $this->assertInstanceOf(Writer::class, $event->writer);
            $eventsTriggered++;
        };

        $event::$beforeSheet = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(BeforeSheet::class, $event);
            $this->assertInstanceOf(Sheet::class, $event->sheet);
            $eventsTriggered++;
        };

        $event::$afterSheet = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(AfterSheet::class, $event);
            $this->assertInstanceOf(Sheet::class, $event->sheet);
            $eventsTriggered++;
        };

        $this->assertInstanceOf(BinaryFileResponse::class, $event->download('filename.xlsx'));
        $this->assertEquals(4, $eventsTriggered);
    }

    /**
     * @test
     */
    public function chunk_events_are_not_called_when_not_queued()
    {
        $event = new ExportWithRegistersEventListeners();

        $eventsTriggered = 0;

        $event::$beforeExport = function ($event) use (&$eventsTriggered) {};

        $event::$beforeWriting = function ($event) use (&$eventsTriggered) {};

        $event::$beforeSheet = function ($event) use (&$eventsTriggered) {};

        $event::$afterSheet = function ($event) use (&$eventsTriggered) {};

        $event::$beforeChunk = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(BeforeChunk::class, $event);
            $eventsTriggered++;
        };

        $event::$afterChunk = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(AfterChunk::class, $event);
            $eventsTriggered++;
        };

        $this->assertInstanceOf(BinaryFileResponse::class, $event->download('filename.xlsx'));
        $this->assertEquals(0, $eventsTriggered);
    }

    /**
     * @test
     */
    public function all_events_get_called_when_export_is_queued()
    {
        $this->loadLaravelMigrations(['--database' => 'testing']);
        $this->withFactories(dirname(__DIR__) . '/Data/Stubs/Database/Factories');
        $this->loadMigrationsFrom(dirname(__DIR__) . '/Data/Stubs/Database/Migrations');
        factory(User::class)->times(100)->create([]);

        $export = new QueuedExportWithStaticEvents();

        $export::$beforeExport = function () {
            Group::query()->create(['name' => 'beforeExport']);
        };

        $export::$beforeWriting = function () {
            Group::query()->create(['name' => 'beforeWriting']);
        };

        $export::$beforeSheet = function () {
            Group::query()->create(['name' => 'beforeSheet']);
        };

        $export::$afterSheet = function () {
            Group::query()->create(['name' => 'afterSheet']);
        };

        $export::$beforeChunk = function () {
            Group::query()->create(['name' => 'beforeChunk']);
        };

        $export::$afterChunk = function () {
            Group::query()->create(['name' => 'afterChunk']);
        };

        $export->queue('queued-export.xlsx')->chain([
            new AfterQueueExportJob(dirname(__DIR__) . '/Data/Disks/Local/queued-export.xlsx'),
        ]);

        // it's 24 because beforeExport, beforeWriting, beforeSheet and afterSheet are fired once
        // and beforeChunk and afterChunk are fired once per chunk, there are 10 chunks
        $this->assertSame(24, Group::query()->count());
    }

    /**
     * @test
     */
    public function events_get_called_when_importing()
    {
        $event = new ImportWithRegistersEventListeners();

        $eventsTriggered = 0;

        $event::$beforeImport = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(BeforeImport::class, $event);
            $this->assertInstanceOf(Reader::class, $event->reader);
            $eventsTriggered++;
        };

        $event::$beforeSheet = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(BeforeSheet::class, $event);
            $this->assertInstanceOf(Sheet::class, $event->sheet);
            $eventsTriggered++;
        };

        $event::$afterSheet = function ($event) use (&$eventsTriggered) {
            $this->assertInstanceOf(AfterSheet::class, $event);
            $this->assertInstanceOf(Sheet::class, $event->sheet);
            $eventsTriggered++;
        };

        $event->import('import.xlsx');
        $this->assertEquals(3, $eventsTriggered);
    }

    /**
     * @test
     */
    public function can_have_invokable_class_as_listener()
    {
        $event = new ExportWithEvents();

        $event->beforeExport = new BeforeExportListener(function ($event) {
            $this->assertInstanceOf(BeforeExport::class, $event);
            $this->assertInstanceOf(Writer::class, $event->writer);
        });

        $this->assertInstanceOf(BinaryFileResponse::class, $event->download('filename.xlsx'));
    }
}