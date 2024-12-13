<?php declare(strict_types=1);

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelService
{
    public function __construct()
    {
    }

    public function prepareExcel($title = "specimens_download")
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle($this->translator->translate('download.excel.title'));

        $spreadsheet->getProperties()
            ->setCreator($this->translator->translate('download.excel.creator'))
            ->setLastModifiedBy($this->translator->translate('download.excel.contributors'))
            ->setTitle($title)
            ->setSubject($this->translator->translate('download.excel.exportDate') . date('d.j.Y', time()))
            ->setDescription("")
            ->setKeywords("JACQ export");

        $spreadsheet->getActiveSheet()->getStyle('A1:DD1')->getFont()->setBold(true);
        return $spreadsheet;
    }

    /**
     * Fills first line with header and from A2 the body
     *
     * @param Spreadsheet $spreadsheet
     * @param array $header
     * @param array $body
     * @return Spreadsheet
     */
    public function easyFillExcel(Spreadsheet $spreadsheet, array $header, array $body): Spreadsheet
    {
        try {
            $spreadsheet->getActiveSheet()->fromArray($header, NULL, 'A1');
            $spreadsheet->getActiveSheet()->fromArray($body, NULL, 'A2');
        } catch (Exception $exception) {
        }
        return $spreadsheet;
    }

    public function setItalic(Spreadsheet $spreadsheet, string $range): Spreadsheet
    {
        try {
            $spreadsheet->getActiveSheet()->getStyle($range)->getFont()->setItalic(TRUE);
        } catch (Exception $exception) {
        }
        return $spreadsheet;
    }

    public function setBold(Spreadsheet $spreadsheet, string $range): Spreadsheet
    {
        try {
            $spreadsheet->getActiveSheet()->getStyle($range)->getFont()->setBold(TRUE);
        } catch (Exception $exception) {
        }
        return $spreadsheet;
    }

    public function setAutosize(Spreadsheet $spreadsheet, array $columns): Spreadsheet
    {
        try {
            foreach ($columns as $column) {
                $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
            }
        } catch (Exception $exception) {
        }
        return $spreadsheet;
    }
}
