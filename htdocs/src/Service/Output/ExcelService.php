<?php declare(strict_types=1);

namespace App\Service\Output;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelService
{
    public const int EXPORT_LIMIT = 1000;

    public const array HEADER = ['Specimen ID', 'observation', 'dig_image', 'dig_img_obs', 'Institution_Code', 'Herbarium-Number/BarCode', 'institution_subcollection', 'Collection Number', 'Type information', 'Typified by', 'Taxon', 'status', 'Genus', 'Species', 'Author', 'Rank', 'Infra_spec', 'Infra_author', 'Family', 'Garden', 'voucher', 'Collection', 'First_collector', 'First_collectors_number', 'Add_collectors', 'Alt_number', 'Series', 'Series_number', 'Coll_Date', 'Coll_Date_2', 'Country', 'Province', 'geonames', 'Latitude', 'Latitude_DMS', 'Lat_Hemisphere', 'Lat_degree', 'Lat_minute', 'Lat_second', 'Longitude', 'Longitude_DMS', 'Long_Hemisphere', 'Long_degree', 'Long_minute', 'Long_second', 'exactness', 'Altitude lower', 'Altitude higher', 'Quadrant', 'Quadrant_sub', 'Location', 'det./rev./conf./assigned', 'ident. history', 'annotations', 'habitat', 'habitus', 'stable identifier'];

    public function prepareExcel($title = "specimens_download")
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle('JACQ specimens export');

        $spreadsheet->getProperties()->setCreator('JACQ')->setLastModifiedBy('JACQ contributors')->setTitle($title)->setSubject('export date: ' . date('d.j.Y', time()))->setDescription("")->setKeywords("JACQ, export");

        $spreadsheet->getActiveSheet()->getStyle('A1:BE1')->getFont()->setBold(true);
        return $spreadsheet;
    }

    /**
     * Fills first line with header and from A2 the body
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
}
