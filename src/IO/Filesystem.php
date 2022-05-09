<?php

namespace App\IO;

use League\Csv\Reader;
use Symfony\Component\Finder\Finder as BaseFinder;

class Filesystem
{
    private string $inputDir;
    private string $outputDir;

    public function __construct(string $inputDir, string $outputDir)
    {
        $this->inputDir = $inputDir;
        $this->outputDir = $outputDir;
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    public function listFiles(array $extensions): array
    {
        $finder = (new BaseFinder())
            ->in($this->inputDir)
        ;

        if (!empty($extensions)) {
            $finder->name(array_map(function (string $extension): string {
                return "*.$extension";
            }, $extensions));
        }

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }

    public function fileExists(string $filename): bool
    {
        return file_exists($this->getFilePath($filename));
    }

    public function readCsv(string $filename, string $delimiter = ',', int $headerOffset = 0): Reader
    {
        return Reader::createFromPath($this->getFilePath($filename))
            ->setHeaderOffset($headerOffset)
            ->setDelimiter($delimiter)
        ;
    }

    private function getFilePath(string $relativePath): string
    {
        return $this->inputDir.'/'.$relativePath;
    }
}
