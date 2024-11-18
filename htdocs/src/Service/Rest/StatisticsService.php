<?php declare(strict_types=1);

namespace App\Service\Rest;

use App\Enum\CoreObjectsEnum;
use App\Enum\TimeIntervalEnum;
use Doctrine\ORM\EntityManagerInterface;


readonly class StatisticsService
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
    }

    protected function getInstitutionsOrdered(): array
    {
        $sql  = "SELECT source_id, source_code FROM meta ORDER BY source_code";

        return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllAssociative();
    }

    protected function getNames(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.taxonID) AS cnt, u.source_id
                                        FROM herbarinput_log.log_tax_species l, herbarinput_log.tbl_herbardb_users u, meta m
                                        WHERE l.userID = u.userID
                                         AND u.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStar
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, u.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    protected function getPeriodColumn(TimeIntervalEnum $interval): string
    {
        switch ($interval) {
            case TimeIntervalEnum::Day:
                return "dayofyear(l.timestamp) AS period";
            case TimeIntervalEnum::Year:
                return "year(l.timestamp) AS period";
            case TimeIntervalEnum::Month:
                return "month(l.timestamp) AS period";
            default :
                return "week(l.timestamp, 1) AS period";
        }
    }

    protected function getCitations(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.citationID) AS cnt, u.source_id
                                        FROM herbarinput_log.log_lit l, herbarinput_log.tbl_herbardb_users u, meta m
                                        WHERE l.userID = u.userID
                                         AND u.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, u.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    protected function getNamesCitations(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.taxindID) AS cnt, u.source_id
                                        FROM herbarinput_log.log_tax_index l, herbarinput_log.tbl_herbardb_users u, meta m
                                        WHERE l.userID = u.userID
                                         AND u.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, u.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    protected function getSpecimens(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.specimenID) AS cnt, mc.source_id
                                        FROM herbarinput_log.log_specimens l, tbl_specimens s, tbl_management_collections mc, meta m
                                        WHERE l.specimenID = s.specimen_ID
                                         AND s.collectionID = mc.collectionID
                                         AND mc.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, mc.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    protected function getTypeSpecimens(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.specimenID) AS cnt, mc.source_id
                                        FROM herbarinput_log.log_specimens l, tbl_specimens s, tbl_management_collections mc, meta m
                                        WHERE l.specimenID = s.specimen_ID
                                         AND s.collectionID = mc.collectionID
                                         AND mc.source_id = m.source_id
                                         AND s.typusID IS NOT NULL
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, mc.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    protected function getNamesTypeSpecimens(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.specimens_types_ID) AS cnt, mc.source_id
                                        FROM herbarinput_log.log_specimens_types l, tbl_specimens s, tbl_management_collections mc, meta m
                                        WHERE l.specimenID = s.specimen_ID
                                         AND s.collectionID = mc.collectionID
                                         AND mc.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, mc.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    protected function getTypesName(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.typecollID) AS cnt, u.source_id
                                        FROM herbarinput_log.log_tax_typecollections l, herbarinput_log.tbl_herbardb_users u, meta m
                                        WHERE l.userID = u.userID
                                         AND u.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, u.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }
    protected function getSynonyms(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.tax_syn_ID) AS cnt, u.source_id
                                        FROM herbarinput_log.log_tbl_tax_synonymy l, herbarinput_log.tbl_herbardb_users u, meta m
                                        WHERE l.userID = u.userID
                                         AND u.source_id = m.source_id
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
                                         AND l.timestamp <= :periodEnd
                                        GROUP BY period, u.source_id
                                        ORDER BY period";
        return $this->entityManager->getConnection()->executeQuery($sql, ["updated"=>$updated, 'periodStart'=>$periodStart,'periodEnd'=>$periodEnd])->fetchAllAssociative();

    }

    /**
     * Get statistics result for given type, interval and period
     *
     * @param string $periodStart start of period (yyyy-mm-dd)
     * @param string $periodEnd end of period (yyyy-mm-dd)
     * @param int $updated new (0) or updated (1) types only
     * @param string $type type of statistics analysis (names, citations, names_citations, specimens, type_specimens, names_type_specimens, types_name, synonyms)
     * @param string $interval resolution of statistics analysis (day, week, month, year)
     * @return array found results
     */
    public function getResults($periodStart, $periodEnd, int $updated, CoreObjectsEnum $type, TimeIntervalEnum $interval)
    {
        switch ($type) {
            // New/Updated names per [interval] -> log_tax_species
            case CoreObjectsEnum::Names:
                $dbRows = $this->getNames($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Citations per [Interval] -> log_lit
            case CoreObjectsEnum::Citations:
                $dbRows = $this->getCitations($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Names used in Citations per [Interval] -> log_tax_index
            case CoreObjectsEnum::Names_citations:
                $dbRows = $this->getNamesCitations($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Specimens per [Interval] -> log_specimens + (straight join) tbl_specimens + (straight join) tbl_management_collections
            case CoreObjectsEnum::Specimens:
                $dbRows = $this->getSpecimens($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Type-Specimens per [Interval] -> log_specimens + (straight join) tbl_specimens where typusID is not null + (straight join) tbl_management_collections
            case CoreObjectsEnum::Type_specimens:
                $dbRows = $this->getTypeSpecimens($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated use of names for Type-Specimens per [Interval] -> log_specimens_types + (straight join) tbl_specimens + (straight join) tbl_management_collections
            case CoreObjectsEnum::Names_type_specimens:
                $dbRows = $this->getNamesTypeSpecimens($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Types per Name per [Interval] -> log_tax_typecollections
            case CoreObjectsEnum::Types_name:
                $dbRows = $this->getTypesName($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Synonyms per [Interval] -> log_tbl_tax_synonymy
            case CoreObjectsEnum::Synonyms:
                $dbRows = $this->getSynonyms($interval, $periodStart, $periodEnd, $updated);
                break;
            // New/Updated Classification entries per [Interval] -> table missing
            case CoreObjectsEnum::Classifications:
            default :
                $dbRows = array();
                break;
        }

        if (count($dbRows) > 0) {
            $result = array();
            $institutionOrder = $this->getInstitutionsOrdered();

            // save source_codes of all institutions
            foreach ($institutionOrder as $institution) {
                $result['results'][$institution['source_id']]['source_code'] = $institution['source_code'];
            }

            $periodMin = $periodMax = $dbRows[0]['period'];
            // set every found statistics result in the respective column and row
            // and find the max and min values of the intervals
            foreach ($dbRows as $dbRow) {
                $periodMin = ($dbRow['period'] < $periodMin) ? $dbRow['period'] : $periodMin;
                $periodMax = ($dbRow['period'] > $periodMin) ? $dbRow['period'] : $periodMax;
                $result['results'][$dbRow['source_id']]['stat'][$dbRow['period']] = $dbRow['cnt'];
            }
            // set the remaining stats of every institution in every given interval with 0
            for ($i = $periodMin; $i <= $periodMax; $i++) {
                foreach ($institutionOrder as $institution) {
                    if (empty($result['results'][$institution['source_id']]['stat'][$i])) {
                        $result['results'][$institution['source_id']]['stat'][$i] = 0;
                    }
                }
            }
            // calculate totals
            foreach ($institutionOrder as $institution) {
                $result['results'][$institution['source_id']]['total'] = array_sum($result['results'][$institution['source_id']]['stat']);
            }
            $result['periodMin'] = $periodMin;
            $result['periodMax'] = $periodMax;
        } else {
            $result = array('periodMin' => 0, 'periodMax' => 0, 'results' => array());
        }

        return $result;
    }


}
