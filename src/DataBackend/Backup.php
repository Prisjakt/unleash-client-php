<?php

namespace Prisjakt\Unleash\DataBackend;

use League\Flysystem\FilesystemInterface;
use Prisjakt\Unleash\DataStorage;

class Backup
{
    const FILENAME = "unleash-backup-file-v1";
    const EXTENSION = ".serialized";

    private $filesystem;
    private $filenameSuffix;

    public function __construct(FilesystemInterface $filesystem = null, $filenameSuffix = "")
    {
        $this->filesystem = $filesystem;
        $this->filenameSuffix = $filenameSuffix;
    }

    /**
     * @return DataStorage|null
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function load()
    {
        $path = $this->getFilePath();
        if ($this->filesystem === null) {
            return null;
        }

        if (!$this->filesystem->has($path)) {
            return null;
        }

        $content = $this->filesystem->read($path);

        if ($content === false) {
            return null;
        }

        return \unserialize($content);
    }

    public function save(DataStorage $dataStorage): bool
    {
        if ($this->filesystem === null) {
            return false;
        }

        $path = $this->getFilePath() . self::EXTENSION;
        if ($this->filesystem->has($path)) {
            return $this->filesystem->update($path, \serialize($dataStorage));
        } else {
            return $this->filesystem->write($path, \serialize($dataStorage));
        }
    }

    private function getFilePath()
    {
        return self::FILENAME . $this->filenameSuffix;
    }
}
