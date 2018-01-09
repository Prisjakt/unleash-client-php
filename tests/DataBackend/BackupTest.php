<?php

namespace Prisjakt\Unleash\Tests\DataBackend;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Prisjakt\Unleash\DataBackend\Backup;
use Prisjakt\Unleash\DataStorage;

class BackupTest extends TestCase
{
    public function testSaveAndLoad()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $backup = new Backup($filesystem);

        $dataStorage = new DataStorage();

        $now = time();
        $eTag = "abc123";
        $dataStorage->setTimestamp($now);
        $dataStorage->setEtag($eTag);

        $backup->save($dataStorage);

        $dataStorage2 = $backup->load();

        $this->assertEquals($now, $dataStorage2->getTimestamp());
        $this->assertEquals($eTag, $dataStorage2->getETag());

        $eTag2 = "def456";
        $dataStorage->setEtag($eTag2);
        $backup->save($dataStorage);

        $dataStorage2 = $backup->load();
        $this->assertEquals($eTag2, $dataStorage2->getETag());
    }

    public function testLoadNoFilesystem()
    {
        $backup = new Backup();

        $this->assertNull($backup->load());
    }

    public function testSaveNoFilesystem()
    {
        $backup = new Backup();

        $dataStorage = new DataStorage();

        $this->assertFalse($backup->save($dataStorage));
    }

    public function testLoadNoBackupFile()
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $backup = new Backup($filesystem);


        $this->assertNull($backup->load());
    }

    public function testSaveWithPrefix()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $backup = new Backup($filesystem, "mango");

        $dataStorage = new DataStorage();

        $now = time();
        $eTag = "abc123";
        $dataStorage->setTimestamp($now);
        $dataStorage->setEtag($eTag);

        $backup->save($dataStorage);

        $dataStorage2 = $backup->load();

        $this->assertEquals($now, $dataStorage2->getTimestamp());
        $this->assertEquals($eTag, $dataStorage2->getETag());
    }
}
