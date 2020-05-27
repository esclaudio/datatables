<?php

use PHPUnit\Framework\TestCase;
use Datatables\Datatable;

final class DatatablesTest extends TestCase
{
    public function testDatatable(): void
    {
        $pdo = $this->getPdo();
        $datatable = new Datatable($pdo, 'test', []);
        $output = $datatable->getOutput();

        $this->assertIsArray($output);
    }

    private function getPdo(): \PDO
    {
        $pdo = new \PDO('sqlite:test.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        return $pdo;
    }
}

