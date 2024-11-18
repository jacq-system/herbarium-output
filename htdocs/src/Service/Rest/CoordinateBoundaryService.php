<?php declare(strict_types=1);

namespace App\Service\Rest;

use Doctrine\ORM\EntityManagerInterface;


readonly class CoordinateBoundaryService
{

    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * check a given coordinate with all known boundaries of a given nation
     *
     * @param int $nationID ID of nation
     * @param float $lat latitude
     * @param float $lon longitude
     * @return array nr of checked boundaries and true if inside, false if outside and null if not checked
     */
    public function nationBoundaries(int $nationID, float $lat, float $lon): array
    {
         $sql = "SELECT bound_south, bound_north, bound_east, bound_west
                                    FROM tbl_geo_nation_geonames_boundaries
                                    WHERE nationID = :nationID";
        $boundaries = $this->entityManager->getConnection()->executeQuery($sql, ['nationID' => $nationID])->fetchAllAssociative();

        return array("nrBoundaries" => count($boundaries),
            "inside"       => $this->checkBoundingBox(floatval($lat), floatval($lon), $boundaries));
    }


    /**
     * check a given coordinate with all known boundaries of a given province
     *
     * @param int $provinceID ÍD of province
     * @param float $lat latitude
     * @param float $lon longitude
     * @return array nr of checked boundaries and true if inside, false if outside and null if not checked     */
    public function provinceBoundaries(int $provinceID, float $lat, float $lon): array
    {
       $sql = "SELECT bound_south, bound_north, bound_east, bound_west
                                    FROM tbl_geo_province_boundaries
                                    WHERE provinceID = :provinceID";
        $boundaries = $this->entityManager->getConnection()->executeQuery($sql, ['provinceID' => $provinceID])->fetchAllAssociative();

        return array("nrBoundaries" => count($boundaries),
            "inside"       => $this->checkBoundingBox(floatval($lat), floatval($lon), $boundaries));
    }


    /**
     * check a list of bounding boxes if coords lie inside
     *
     * @param float $lat latitude
     * @param float $lon longitude
     * @param array $boundaries list of boundaries (if any)
     * @return bool|null true if inside, false if outside, null if list of boundaries is empty
     */
    protected function checkBoundingBox(float $lat, float $lon, array $boundaries): ?bool
    {
        if (!empty($boundaries)) {
            foreach ($boundaries as $boundary) {
                if ($lat >= $boundary['bound_south'] && $lat <= $boundary['bound_north']
                    && (($boundary['bound_east'] > $boundary['bound_west'] && ($lon >= $boundary['bound_west'] && $lon <= $boundary['bound_east']))
                        || ($boundary['bound_east'] < $boundary['bound_west'] && ($lon >= $boundary['bound_west'] || $lon <= $boundary['bound_east'])))) {
                    return true;
                }
            }
            return false;
        } else {
            return null;
        }
    }
}
