<?php declare(strict_types=1);

namespace App\Service\Tools;

use JACQ\Enum\CoreObjectsEnum;
use JACQ\Enum\TimeIntervalEnum;
use JACQ\Repository\Herbarinput\InstitutionRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class StatisticsService
{
    public function __construct(protected EntityManagerInterface $entityManager, protected InstitutionRepository $institutionRepository)
    {
    }

    protected function getNames(TimeIntervalEnum $interval, string $periodStart, string $periodEnd, int $updated): array
    {
        $sql = "SELECT ".$this->getPeriodColumn($interval).", count(l.taxonID) AS cnt, u.source_id
                                        FROM herbarinput_log.log_tax_species l, herbarinput_log.tbl_herbardb_users u
                                        WHERE l.userID = u.userID
                                         AND l.updated = :updated
                                         AND l.timestamp >= :periodStart
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
                                        FROM herbarinput_log.log_lit l, herbarinput_log.tbl_herbardb_users u
                                        WHERE l.userID = u.userID
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
                                        FROM herbarinput_log.log_tax_index l, herbarinput_log.tbl_herbardb_users u
                                        WHERE l.userID = u.userID
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
                                        FROM herbarinput_log.log_specimens l, tbl_specimens s, tbl_management_collections mc
                                        WHERE l.specimenID = s.specimen_ID
                                         AND s.collectionID = mc.collectionID
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
                                        FROM herbarinput_log.log_specimens l, tbl_specimens s, tbl_management_collections mc
                                        WHERE l.specimenID = s.specimen_ID
                                         AND s.collectionID = mc.collectionID
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
                                        FROM herbarinput_log.log_specimens_types l, tbl_specimens s, tbl_management_collections mc
                                        WHERE l.specimenID = s.specimen_ID
                                         AND s.collectionID = mc.collectionID
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
                                        FROM herbarinput_log.log_tax_typecollections l, herbarinput_log.tbl_herbardb_users u
                                        WHERE l.userID = u.userID
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
                                        FROM herbarinput_log.log_tbl_tax_synonymy l, herbarinput_log.tbl_herbardb_users u
                                        WHERE l.userID = u.userID
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
     * @param CoreObjectsEnum $type type of statistics analysis (names, citations, names_citations, specimens, type_specimens, names_type_specimens, types_name, synonyms)
     * @param TimeIntervalEnum $interval resolution of statistics analysis (day, week, month, year)
     * @return array found results
     */
    public function getResults($periodStart, $periodEnd, int $updated, CoreObjectsEnum $type, TimeIntervalEnum $interval)
    {
        $dbRows = match ($type) {
            CoreObjectsEnum::Names => $this->getNames($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Citations => $this->getCitations($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Names_citations => $this->getNamesCitations($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Specimens => $this->getSpecimens($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Type_specimens => $this->getTypeSpecimens($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Names_type_specimens => $this->getNamesTypeSpecimens($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Types_name => $this->getTypesName($interval, $periodStart, $periodEnd, $updated),
            CoreObjectsEnum::Synonyms => $this->getSynonyms($interval, $periodStart, $periodEnd, $updated),
            default => array(),
        };

        if (count($dbRows) > 0) {
            $result = array();
//        $sql  = "SELECT MetadataID as source_id, SourceInstitutionID as source_code FROM metadata ORDER BY source_code";
            $institutions = $this->institutionRepository->findBy([],['code' => 'ASC']);

            // save source_codes of all institutions
            foreach ($institutions as $institution) {
                $result['results'][$institution->getId()]['source_code'] = $institution->getCode();
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
                foreach ($institutions as $institution) {
                    if (empty($result['results'][$institution->getId()]['stat'][$i])) {
                        $result['results'][$institution->getId()]['stat'][$i] = 0;
                    }
                }
            }
            // calculate totals
            foreach ($institutions as $institution) {
                $result['results'][$institution->getId()]['total'] = array_sum($result['results'][$institution->getId()]['stat']);
            }
            $result['periodMin'] = $periodMin;
            $result['periodMax'] = $periodMax;
        } else {
            $result = array('periodMin' => 0, 'periodMax' => 0, 'results' => array());
        }

        return $result;
    }


}
