<?php declare(strict_types=1);

namespace App\Facade\Rest;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class ClassificationDownloadFacade
{

    /**
     * hide scientific name authors in output file
     */
    protected bool $hideScientificNameAuthors = false;

    /**
     * header-line, pre-filled with fixed titles
     */
    protected array $outputHeader = array(
        "reference_guid",
        "reference",
        "license",
        "downloaded",
        "modified",
        "scientific_name_guid",
        "scientific_name_id",
        "parent_scientific_name_id",
        "accepted_scientific_name_id",
        "taxonomic_status");

    /**
     * fill with amount of prefixed headers
     */
    protected int $outputHeaderPrefixLen;

    /**
     * body lines
     */
    private array $outputBody = [];

    public function __construct(protected EntityManagerInterface $entityManager, protected RouterInterface $router)
    {
    }

    /**
     * create an array, filled with header and data for download
     *
     * @param string $referenceType Type of reference (only 'citation' allowed at this time)
     * @param int $referenceId ID of reference
     * @param int $scientificNameId optional ID of scientific name
     * @param mixed $hideScientificNameAuthors hide authors name in scientific name (default = use database)
     * @return array data for download
     */
    public function getDownload(string $referenceType, int $referenceId, ?int $scientificNameId = 0, ?int $hideScientificNameAuthors = null): array
    {
        if (empty($referenceType) || empty($referenceId)) {
            return array();
        }

        switch ($hideScientificNameAuthors) {
            case 1:
                $this->hideScientificNameAuthors = true;
                break;
            case 0:
                $this->hideScientificNameAuthors = false;
                break;
            default:
                // if hide scientific name authors is null, use preference from literature entry
                $this->hideScientificNameAuthors = $this->shouldHideScientificNameAuthors($referenceId);
                break;
        }

        $sql = "SELECT tsy.source_citationID, tsy.taxonID, tsy.acc_taxon_ID,
                   tr.rank_hierarchy,
                   tc.parent_taxonID,
                   `herbar_view`.GetProtolog(l.citationID) AS citation
            FROM tbl_tax_synonymy tsy
             LEFT JOIN tbl_tax_species ts ON ts.taxonID = tsy.taxonID
             LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID = ts.tax_rankID
             LEFT JOIN tbl_lit l ON l.citationID = tsy.source_citationID
             LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = tsy.tax_syn_ID ";
        // check if a certain scientific name id is specified & load the fitting synonymy entry
        if ($scientificNameId > 0) {
            $sql .= " WHERE tsy.source_citationID = :referenceId
                                                          AND tsy.acc_taxon_ID IS NULL
                                                          AND tsy.taxonID = :scientificNameId";
        } // if not, fetch all top-level entries for this reference
        else {
            $sql .= " WHERE tsy.source_citationID = :referenceId
                                                        AND tsy.acc_taxon_ID IS NULL
                                                        AND tc.classification_id IS NULL";
        }

        $dbRowsTaxSynonymy = $this->entityManager->getConnection()->executeQuery($sql, ['referenceId' => $referenceId, 'scientificNameId' => $scientificNameId])->fetchAllAssociative();

        $this->outputHeaderPrefixLen = count($this->outputHeader);

        // fetch all ranks, sorted by hierarchy for creating the headings of the download
        $tax_ranks = $this->getRankHierarchies();
        foreach ($tax_ranks as $rank) {
            $this->outputHeader[$this->outputHeaderPrefixLen - 1 + $rank['hierarchy']] = $rank['rank'];
        }

        // cycle through top-level elements and continue exporting their children
        foreach ($dbRowsTaxSynonymy as $dbRowTaxSynonymy) {
            $this->exportClassification(array(), $dbRowTaxSynonymy);
        }

        return array('header' => $this->outputHeader, 'body' => $this->outputBody);
    }

    /**
     * what tells tbl_lit about hiding scientific name authors
     *
     * @param int $referenceId citationID
     * @return int hide (1) or show (0) author name
     */
    protected function shouldHideScientificNameAuthors(int $referenceId): bool
    {
        $sql = "SELECT hideScientificNameAuthors
                             FROM tbl_lit
                             WHERE citationID = :referenceId";
        return (bool) $this->entityManager->getConnection()->executeQuery($sql, ['referenceID' => $referenceId])->fetchOne();
    }

    /**
     * get all hierarchy names and numbers
     *
     * @return array list of all tax_rank hierarchies ['rank'] and numbers ['hierarchy']
     */
    protected function getRankHierarchies(): array
    {
        $sql = "SELECT rank, rank_hierarchy AS hierarchy
                             FROM tbl_tax_rank
                             ORDER BY rank_hierarchy ASC";
        return $this->entityManager->getConnection()->executeQuery($sql)->fetchAllAssociative();
    }

    /**
     * Map a given tax synonymy entry to an array, including all children recursively
     *
     * @param array $parentTaxSynonymies an array of db-rows of all parent tax-synonymy entries
     * @param array $taxSynonymy db-row of the currently active tax-synonym entry
     */
    protected function exportClassification($parentTaxSynonymies, $taxSynonymy)
    {

        $line[0] = 'TODO'; // TODO $this->getUuidUrl('citation', $taxSynonymy['source_citationID']);
        $line[1] = $taxSynonymy['citation'];
        $line[2] = 'TODO'; // TODO $this->settings['classifications_license'];  licence is depending on some app configuration? should be stored with data as it is fixed..?
        $line[3] = date("Y-m-d H:i:s");
        $line[4] = '';
        $line[5] = 'TODO'; // TODO $this->getUuidUrl('scientific_name', $taxSynonymy['taxonID']);
        $line[6] = $taxSynonymy['taxonID'];
        $line[7] = $taxSynonymy['parent_taxonID'];
        $line[8] = $taxSynonymy['acc_taxon_ID'];
        $line[9] = ($taxSynonymy['acc_taxon_ID']) ? 'synonym' : 'accepted';

        // add parent information
        foreach ($parentTaxSynonymies as $parentTaxSynonymy) {
            $line[$this->outputHeaderPrefixLen + $parentTaxSynonymy['rank_hierarchy'] - 1] = $this->getScientificName($parentTaxSynonymy['taxonID']);
        }

        // add the currently active information
        $line[$this->outputHeaderPrefixLen + $taxSynonymy['rank_hierarchy'] - 1] = $this->getScientificName($taxSynonymy['taxonID']);

        $this->outputBody[] = $line;

        // fetch all synonyms
        $sql = "SELECT tsy.source_citationID, tsy.taxonID, tsy.acc_taxon_ID,
                                                    tr.rank_hierarchy,
                                                    tc.parent_taxonID,
                                                    `herbar_view`.GetProtolog(l.citationID) AS citation
                                             FROM tbl_tax_synonymy tsy
                                              LEFT JOIN tbl_tax_species ts ON ts.taxonID = tsy.taxonID
                                              LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID = ts.tax_rankID
                                              LEFT JOIN tbl_lit l ON l.citationID = tsy.source_citationID
                                              LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = tsy.tax_syn_ID
                                             WHERE tsy.source_citationID = :source_citationID
                                              AND tsy.acc_taxon_ID = :taxonID";
        $taxSynonymySynonyms = $this->entityManager->getConnection()->executeQuery($sql, ['source_citationID' => $taxSynonymy['source_citationID'], 'taxonID' =>$taxSynonymy['taxonID'] ])->fetchAllAssociative();
        foreach ($taxSynonymySynonyms as $taxSynonymySynonym) {
            $this->exportClassification($parentTaxSynonymies, $taxSynonymySynonym);
        }

        // fetch all children
        $parentTaxSynonymies[] = $taxSynonymy;
        $sql = "SELECT tsy.source_citationID, tsy.taxonID, tsy.acc_taxon_ID,
                                                    tr.rank_hierarchy,
                                                    tc.parent_taxonID,
                                                    `herbar_view`.GetProtolog(l.citationID) AS citation
                                             FROM tbl_tax_synonymy tsy
                                              LEFT JOIN tbl_tax_species ts ON ts.taxonID = tsy.taxonID
                                              LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID = ts.tax_rankID
                                              LEFT JOIN tbl_lit l ON l.citationID = tsy.source_citationID
                                              LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = tsy.tax_syn_ID
                                             WHERE tsy.source_citationID = :source_citationID
                                              AND tc.parent_taxonID = :taxonID
                                             ORDER BY tc.order ASC";
        $taxSynonymyChildren = $this->entityManager->getConnection()->executeQuery($sql, ['source_citationID' => $taxSynonymy['source_citationID'], 'taxonID' =>$taxSynonymy['taxonID'] ])->fetchAllAssociative();

        foreach ($taxSynonymyChildren as $taxSynonymyChild) {
            $this->exportClassification($parentTaxSynonymies, $taxSynonymyChild);
        }

    }

    /**
     * get scientific name from database
     */
    protected function getScientificName(int $taxonID): ?string
    {
        $sql = "CALL herbar_view._buildScientificNameComponents(:taxonID, @scientificName, @author)";
        $this->entityManager->getConnection()->executeQuery($sql, ['taxonID' => $taxonID]);
        $name = $this->entityManager->getConnection()->executeQuery("SELECT @scientificName, @author")->fetchAssociative();

        if ($name) {
            $scientificName = $name['@scientificName'];
            if (!$this->hideScientificNameAuthors) {
                $scientificName .= ' ' . $name['@author'];
            }
        } else {
            return null;
        }

        return $scientificName;
    }

    /**
     * use input-webservice "uuid" to get the uuid-url for a given id and type
     *
     * @param mixed $type type of uuid (1 or scientific_name, 2 or citation 3 or specimen)
     * @param int $id internal-id of uuid
     * @return string uuid-url returned from webservice
     * TODO - this function is just copied for evidence and should be somehow rewritten,but I wasn't able to understood the logic of UUID in JACQ
     */
    private function getUuidUrl ($type, $id)
    {
        $curl = curl_init($this->settings['jacq_input_services'] . "tags/uuid/$type/$id");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('APIKEY: ' . $this->settings['apikey']));
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $result = '';
        } else {
            $json = json_decode($curl_response, true);
            $result = $json['url'];
        }
        curl_close($curl);

        return $result;
    }
}
