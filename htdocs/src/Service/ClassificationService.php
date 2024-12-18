<?php declare(strict_types=1);

namespace App\Service;

readonly class ClassificationService extends BaseService
{

    public function getList(array $criteria = [], array $sourceIds = [10400, 26389]): array         //TODO ?default values hardcoded
    {
        $constraints = array();
        if (!empty($criteria['organisationIds'])) {
            $constraints[] = "organisation_id IN (" . implode(', ', $criteria['organisationIds']) . ")";
        }
        if (isset($criteria['separated'])) {
            $constraints[] = "separated = " . intval($criteria['separated']);
        }
        if (isset($criteria['derivativeID'])) {
            $constraints[] = "derivative_id = " . intval($criteria['derivativeID']);
        }

        $ret = array();
        //TODO using variables in col names!
        $sql = "(SELECT * FROM view_botanical_object_living " . (($constraints) ? "WHERE " . implode(" AND ", $constraints) : '') . ")
                              UNION
                              (SELECT * FROM view_botanical_object_vegetative " . (($constraints) ? "WHERE " . implode(" AND ", $constraints) : '') . ")";
        $rows = $this->query($sql)->fetchAllAssociative();

        $protolog[0] = null;  // for empty $family['source_id']
        foreach ($rows as $row) {
            $name = $this->getScientificName($row['scientific_name_id']);
            $family = $this->getFamily($row['scientific_name_id'], $sourceIds);
            $derivative = $this->getDerivative($row['derivative_id']);
            $botanicalObject = $this->getBotanicalObject($row['botanical_object_id']);
            $livingPlant = $this->getLivingPlant($row['derivative_id']);
            $scNameInfo = $this->getScientificNameInfo($row['scientific_name_id']);
            $acquisition = $this->getAcquisition($botanicalObject['acquisition_event_id']);
            $acPersons = $this->getPersonOfAcquisition($botanicalObject['acquisition_event_id']);
            if (!empty($family['source_id']) && empty($protolog[$family['source_id']])) { // use caching
                $protolog[$family['source_id']] = $this->getProtologOfSource($family['source_id'])['protolog'];
            }
            if (!empty($livingPlant['label_synonym_scientific_name_id'])) {
                $buffer = $this->getScientificName($livingPlant['label_synonym_scientific_name_id']);
                $labelSynonymScientificName = $buffer['scientific_name'];
            } else {
                $labelSynonymScientificName = null;
            }
            if (!empty($livingPlant['index_seminum_type_id'])) {
                $indexSeminumType = $this->query("SELECT type FROM tbl_index_seminum_type WHERE id = :id", ['id' => $livingPlant['index_seminum_type_id']])->fetchOne();
            } else {
                $indexSeminumType = null;
            }
            if (!empty($acPersons)) {
                $collectorsList = array();
                foreach ($acPersons as $acPerson) {
                    $collectorsList[] = $acPerson['name'];
                }
            } else {
                $collectorsList = null;
            }
            if (!empty($acquisition['lat_d']) && !empty($acquisition['lat_m']) && !empty($acquisition['lat_s'])) {
                $lat = (($acquisition['lat_NS'] == 'S') ? '-' : '') . "{$acquisition['lat_d']}.{$acquisition['lat_m']}.{$acquisition['lat_s']}";
            } else {
                $lat = null;
            }
            if (!empty($acquisition['lon_d']) && !empty($acquisition['lon_m']) && !empty($acquisition['lon_s'])) {
                $lon = (($acquisition['lon_EW'] == 'W') ? '-' : '') . "{$acquisition['lon_d']}.{$acquisition['lon_m']}.{$acquisition['lon_s']}";
            } else {
                $lon = null;
            }
            $ret[] = array('ID' => $row['derivative_id'], 'Wissenschaftlicher Name' => $row['scientific_name'], 'scientificNameId' => $row['scientific_name_id'], 'Standort' => $row['organisation_description'], 'Akzessionsnummer' => $row['accession_number'], 'Ort' => $row['gathering_location'], 'Platznummer' => $row['place_number'], 'Familie' => $family['scientificName'] ?? null, 'Synonym für Etikett' => $labelSynonymScientificName, 'Volksnamen' => $scNameInfo['common_names'] ?? null, 'Verbreitung' => $scNameInfo['spatial_distribution'] ?? null, 'Familie Referenz' => $protolog[($family['source_id'] ?? 0)] ?? null, 'Anmerkung für Etikett' => $row['label_annotation'], 'Wissenschaftlicher Name ohne Autor' => $name['scientific_name_no_author'] ?? null, 'Wissenschaftlicher Name Author' => $name['scientific_name_author'] ?? null, 'Familie ohne Author' => $family['scientificNameNoAuthor'] ?? null, 'Familie Author' => $family['scientificNameAuthor'] ?? null, 'Art' => $indexSeminumType, 'IPEN Nummer' => $row['ipen_number'], 'Lebensraum' => $botanicalObject['habitat'], 'Sammelnummer' => $acquisition['number'], 'Altitude Min' => $acquisition['altitude_min'], 'Altitude Max' => $acquisition['altitude_max'], 'Breitengrad' => $lat, 'Längengrad' => $lon, 'Sammeldatum' => ($acquisition['custom']) ?: "{$acquisition['day']}.{$acquisition['month']}.{$acquisition['year']}", 'Sammler-Name(n)' => ($collectorsList) ? implode(',', $collectorsList) : null, 'Sorte' => $row['cultivar_name'], 'Anzahl' => $derivative['count'], 'Preis' => $derivative['price']);
        }

        return $ret;
    }

    protected function getScientificName(int $scientificNameId): ?array
    {
        $sql = "SELECT scientific_name_id, scientific_name, scientific_name_no_author, scientific_name_author
                             FROM view_scientificName
                             WHERE scientific_name_id = :scientificNameId";
        $row = $this->query($sql, ['scientificNameId' => $scientificNameId])->fetchAssociative();

        return $row ?? null;
    }

    protected function getFamily(int $scientificNameId, array $sourceIds): ?array
    {
        foreach ($sourceIds as $sourceId) {
            $classification = $this->getClassification($scientificNameId, $sourceId);
            if (empty($classification)) {
                $sql = "SELECT name_id, substantive_id, rank_id
                                     FROM mig_nom_name
                                     WHERE name_id = :scientificNameId";
                $row = $this->query($sql, ['scientificNameId' => $scientificNameId])->fetchAssociative();
                if (!empty($row)) {
                    // check if this is already a genus entry
                    if (empty($row['rank_id']) || $row['rank_id'] == 7) {
                        continue;
                    }
                    $sql = "SELECT name_id
                              FROM mig_nom_name
                              WHERE substantive_id = :substantive_id
                               AND rank_id = 7";
                    $genusRow = $this->query($sql, ['substantive_id' => $row['substantive_id']])->fetchAssociative();

                    if (!empty($genusRow['name_id'])) {
                        $genusFamilyName = $this->getFamily($genusRow['name_id'], $sourceIds);
                        if (!empty($genusFamilyName)) {
                            return $genusFamilyName;
                        }
                    }
                }
                // if we did not find any entry using the genus, continue with next reference
            } else {
                $sql = "SELECT rank_id
                           FROM mig_nom_name
                           WHERE name_id = :scientific_name_id";
                $rankId = $this->query($sql, ['scientific_name_id' => $classification['scientific_name_id']])->fetchOne();

                while ($rankId != 9 && !empty($classification['parent_scientific_name_id'])) {
                    $classification = $this->getClassification($classification['parent_scientific_name_id'], $sourceId);
                    if (empty($classification)) {
                        break;
                    }
                    $sql = "SELECT rank_id
                           FROM mig_nom_name
                           WHERE name_id = :scientific_name_id";
                    $rankId = $this->query($sql, ['scientific_name_id' => $classification['scientific_name_id']])->fetchOne();
                }
                // if no family ranked name was found, continue with next reference
                if ($rankId === false || empty($classification) || ($rankId != 9 && empty($classification['parent_scientific_name_id']))) {
                    continue;
                }
                $scientificName = $this->getScientificName($classification['scientific_name_id']);
                if (empty($scientificName)) {
                    return null;
                }
                return array("scientificNameId" => $scientificName['scientific_name_id'] ?? '', "scientificName" => $scientificName['scientific_name'] ?? '', "scientificNameNoAuthor" => $scientificName['scientific_name_no_author'] ?? '', "scientificNameAuthor" => $scientificName['scientific_name_author'] ?? '', "source_id" => $classification['source_id']);
            }
        }

        return null;    // we've found nothing
    }

    protected function getClassification(int $scientificNameId, int $sourceId): bool|array|null
    {
        $sql = "SELECT classification_id, scientific_name_id, acc_scientific_name_id, parent_scientific_name_id, source_id
                             FROM tbl_classification
                             WHERE scientific_name_id = :scientificNameId
                              AND source_id = :sourceId
                              AND source = 'CITATION'";
        $row = $this->query($sql, ['sourceId' => $sourceId, 'scientificNameId' => $scientificNameId])->fetchAssociative();

        if ($row === false) {
            return null;
        }
        if (!empty($row['acc_scientific_name_id'])) {
            $sql = "SELECT classification_id, scientific_name_id, acc_scientific_name_id, parent_scientific_name_id, source_id
                             FROM tbl_classification
                             WHERE scientific_name_id = :acc_scientific_name_id
                              AND source_id = :sourceId
                              AND source = 'CITATION'";
            $row = $this->query($sql, ['sourceId' => $sourceId, 'acc_scientific_name_id' => $row['acc_scientific_name_id']])->fetchAssociative();
            if ($row === false) {
                return null;
            }
        }
        return $row;
    }

    protected function getDerivative(int $id): ?array
    {
        $sql = "SELECT count, price FROM tbl_derivative WHERE derivative_id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

    protected function getBotanicalObject(int $id): ?array
    {
        $sql = "SELECT * FROM tbl_botanical_object WHERE id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

    protected function getLivingPlant(int $id): ?array
    {
        $sql = "SELECT * FROM tbl_living_plant WHERE id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

    protected function getScientificNameInfo(int $id): ?array
    {
        $sql = "SELECT * FROM tbl_scientific_name_information WHERE scientific_name_id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

    protected function getAcquisition(int $id): ?array
    {
        $sql = "SELECT ae.number, ae.annotation, ad.year, ad.month, ad.day, ad.custom,
               lc.altitude_min, lc.altitude_max,
               lc.latitude_half AS lat_NS, lc.latitude_degrees AS lat_d, lc.latitude_minutes AS lat_m, lc.latitude_seconds AS lat_s,
               lc.longitude_half AS lon_EW, lc.longitude_degrees AS lon_d, lc.longitude_minutes AS lon_m, lc.longitude_seconds AS lon_s
              FROM tbl_acquisition_event ae
               LEFT JOIN tbl_acquisition_date ad     ON ad.id = ae.acquisition_date_id
               LEFT JOIN tbl_location_coordinates lc ON lc.id = ae.location_coordinates_id
              WHERE ae.id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

    protected function getPersonOfAcquisition(int $id): ?array
    {
        $sql = "SELECT  p.name
              FROM tbl_acquisition_event_person aep
               LEFT JOIN tbl_person p ON p.id = aep.person_id
              WHERE acquisition_event_id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

    protected function getProtologOfSource(int $id): ?array
    {
        $sql = "SELECT protolog FROM view_protolog WHERE citation_id = :id";
        $result = $this->query($sql, ['id' => $id])->fetchAssociative();
        if ($result !== false) {
            return $result;
        }
        return null;
    }

}
