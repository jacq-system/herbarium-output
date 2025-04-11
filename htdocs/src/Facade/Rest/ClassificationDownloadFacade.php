<?php declare(strict_types=1);

namespace App\Facade\Rest;


use App\Entity\Jacq\Herbarinput\Literature;
use App\Entity\Jacq\Herbarinput\Synonymy;
use App\Repository\Herbarinput\TaxonRankRepository;
use App\Service\TaxonService;
use App\Service\UuidConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\RouterInterface;

class ClassificationDownloadFacade
{

    protected bool $hideScientificNameAuthors = false;
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
    private array $outputBody = [];

    public function __construct(protected EntityManagerInterface $entityManager, protected RouterInterface $router, protected UuidConfiguration $uuidConfiguration, protected TaxonRankRepository $taxonRankRepository, protected readonly TaxonService $taxonService)
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
            return [];
        }

        $this->detectAuthorsVisibility($referenceId, $hideScientificNameAuthors);
        foreach ($this->taxonRankRepository->getRankHierarchies() as $rank) {
            $this->outputHeader[$this->headerLength() - 1 + $rank['hierarchy']] = $rank['name'];
        }

        $queryBuilder = $this->getBaseQueryBuilder()
            ->andWhere('a.actualTaxonId IS NULL')
            ->setParameter('reference', $referenceId)
            ->setParameter('scientificNameId', $scientificNameId);

        // check if a certain scientific name id is specified & load the fitting synonymy entry
        if ($scientificNameId > 0) {
            $queryBuilder = $queryBuilder
                ->join('a.species', 'sp')
                ->andWhere('sp.id = :scientificNameId');
        } // if not, fetch all top-level entries for this reference
        else {
            $queryBuilder = $queryBuilder->leftJoin('a.classification', 'clas')->andWhere('class.id IS NOT NULL');
        }

        // cycle through top-level elements and continue exporting their children
        foreach ($queryBuilder->getQuery()->getResult() as $dbRowTaxSynonymy) {
            $this->exportClassification(array(), $dbRowTaxSynonymy);
        }

        return array('header' => $this->outputHeader, 'body' => $this->outputBody);
    }

    protected function detectAuthorsVisibility(int $referenceId, ?int $hideScientificNameAuthors = null): void
    {
        switch ($hideScientificNameAuthors) {
            case 1:
                $this->hideScientificNameAuthors = true;
                break;
            case 0:
                $this->hideScientificNameAuthors = false;
                break;
            default:
                // if hide scientific name authors is null, use preference from literature entry
                $this->hideScientificNameAuthors = $this->entityManager->getRepository(Literature::class)->find($referenceId)->isHideScientificNameAuthors();
                break;
        }
    }

    protected function headerLength(): int
    {
        return count($this->outputHeader);
    }

    protected function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->getRepository(Synonymy::class)->createQueryBuilder('a')
            ->join('a.literature', 'lit')
            ->andWhere('lit.id = :reference');
    }

    /**
     * Map a given tax synonymy entry to an array, including all children recursively
     *
     * @param array $parentTaxSynonymies an array of db-rows of all parent tax-synonymy entries
     * @param array $taxSynonymy db-row of the currently active tax-synonym entry
     */
    protected function exportClassification($parentTaxSynonymies, Synonymy $taxSynonymy)
    {

        $line[0] = $this->getUuidUrl('citation', $taxSynonymy->getLiterature()->getId());
        $line[1] = $this->entityManager->getRepository(Literature::class)->getProtolog($taxSynonymy->getLiterature()->getId());
        $line[2] = 'CC-BY-SA'; // TODO in original $this->settings['classifications_license'];  licence is depending on some app configuration? should be stored with data as it is fixed..?
        $line[3] = date("Y-m-d H:i:s");
        $line[4] = '';
        $line[5] = $this->getUuidUrl('scientific_name', $taxSynonymy->getSpecies()->getId());
        $line[6] = $taxSynonymy->getSpecies()->getId();
        $line[7] = $taxSynonymy->getClassification()->getParentTaxonId();
        $line[8] = $taxSynonymy->getActualTaxonId();
        $line[9] = ($taxSynonymy->getActualTaxonId()) ? 'synonym' : 'accepted';

        // add parent information
        foreach ($parentTaxSynonymies as $parentTaxSynonymy) {
            /** @var Synonymy $parentTaxSynonymy */
            $line[$this->headerLength() + $parentTaxSynonymy->getSpecies()->getRank()->getHierarchy() - 1] = $this->taxonService->getScientificName($parentTaxSynonymy->getSpecies()->getId(), $this->hideScientificNameAuthors);
        }

        // add the currently active information
        $line[$this->headerLength() + $taxSynonymy->getSpecies()->getRank()->getHierarchy() - 1] = $this->taxonService->getScientificName($taxSynonymy->getSpecies()->getId(), $this->hideScientificNameAuthors);

        $this->outputBody[] = $line;

        $queryBuilder = $this->getBaseQueryBuilder()
            ->andWhere('a.actualTaxonId = :taxon')
            ->setParameter('reference', $taxSynonymy->getLiterature()->getId())
            ->setParameter('taxon', $taxSynonymy->getActualTaxonId());

        // fetch all synonyms
        foreach ($queryBuilder->getQuery()->getResult() as $taxSynonymySynonym) {
            $this->exportClassification($parentTaxSynonymies, $taxSynonymySynonym);
        }

        // fetch all children
        $parentTaxSynonymies[] = $taxSynonymy;
        $queryBuilder = $this->getBaseQueryBuilder()
            ->join('a.classification', 'clas')
            ->andWhere('clas.parentTaxonId = :taxon')
            ->setParameter('reference', $taxSynonymy->getLiterature()->getId())
            ->setParameter('taxon', $taxSynonymy->getActualTaxonId())
            ->orderBy('clas.sort', 'ASC');

        foreach ($queryBuilder->getQuery()->getResult() as $taxSynonymyChild) {
            $this->exportClassification($parentTaxSynonymies, $taxSynonymyChild);
        }

    }

    /**
     * use input-webservice "uuid" to get the uuid-url for a given id and type
     *
     * @param mixed $type type of uuid (1 or scientific_name, 2 or citation 3 or specimen)
     * @param int $id internal-id of uuid
     * @return string uuid-url returned from webservice
     * TODO - this architecture is not good
     */
    private function getUuidUrl($type, $id)
    {
        $curl = curl_init($this->uuidConfiguration->endpoint . "tags/uuid/$type/$id");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('APIKEY: ' . $this->uuidConfiguration->secret));
        $curl_response = curl_exec($curl);
        if ($curl_response !== false) {
            $json = json_decode($curl_response, true);
            if (isset($json['url'])) {
                curl_close($curl);
                return $json['url'];
            }

        }
        curl_close($curl);
        return '';
    }

}
