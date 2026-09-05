<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DatabaseExportSafetyTest extends TestCase
{
    public function test_database_no_contiene_exportadores_y_el_dump_esta_ignorado(): void
    {
        $raiz = dirname(__DIR__, 2);
        $archivos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz.'/database'));

        foreach ($archivos as $archivo) {
            if (!$archivo->isFile() || $archivo->getExtension() !== 'php') {
                continue;
            }

            $this->assertStringNotContainsString(
                'mysqldump',
                strtolower(file_get_contents($archivo->getPathname())),
                'Un archivo de database no debe exportar la base: '.$archivo->getFilename()
            );
        }

        $this->assertContains('/database/dump.sql', file($raiz.'/.gitignore', FILE_IGNORE_NEW_LINES));
        $this->assertStringNotContainsString(
            'fase10Dump',
            file_get_contents($raiz.'/database/seeders/DemoSeeder.php')
        );
    }
}
