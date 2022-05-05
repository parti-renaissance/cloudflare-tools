<?php

namespace App\Command;

use League\Csv\Reader;

trait CsvCommandTrait
{
    protected static function readCsv(string $filename, int $headerOffset = 0, string $delimiter = ','): Reader
    {
        return Reader::createFromString(file_get_contents($filename))
            ->setHeaderOffset($headerOffset)
            ->setDelimiter($delimiter)
        ;
    }
}
