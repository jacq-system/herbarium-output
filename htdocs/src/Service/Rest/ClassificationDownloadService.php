<?php declare(strict_types=1);

namespace App\Service\Rest;


use JACQ\Entity\Jacq\Herbarinput\Synonymy;
use JACQ\Repository\Herbarinput\LiteratureRepository;
use JACQ\Repository\Herbarinput\SynonymyRepository;
use JACQ\Repository\Herbarinput\TaxonRankRepository;
use JACQ\Service\SpeciesService;
use JACQ\Service\UuidService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Routing\RouterInterface;

class ClassificationDownloadService
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

    protected array $rankKeys = [];
    private array $outputBody = [];

    public function __construct(protected RouterInterface $router, protected UuidService $uuidService, protected TaxonRankRepository $taxonRankRepository, protected readonly SpeciesService $taxonService, protected readonly LiteratureRepository $literatureRepository, protected readonly SynonymyRepository $synonymyRepository)
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
        $this->prepareHeader();

        $queryBuilder = $this->getBaseQueryBuilder()
            ->andWhere('a.actualTaxonId IS NULL')
            ->setParameter('reference', $referenceId)
            ->setParameter('scientificNameId', $scientificNameId);

        // check if a certain scientific name id is specified & load the fitting synonymy entry
        if ($scientificNameId > 0) {
            $queryBuilder = $queryBuilder
                ->leftJoin('a.species', 'sp')
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
                $this->hideScientificNameAuthors = $this->literatureRepository->find($referenceId)->isHideScientificNameAuthors();
                break;
        }
    }

    protected function prepareHeader(): void
    {
        foreach ($this->taxonRankRepository->getRankHierarchies() as $rank) {
            $this->rankKeys[$rank['hierarchy']] = count($this->outputHeader) - 1 + $rank['hierarchy'];
        }
        foreach ($this->taxonRankRepository->getRankHierarchies() as $rank) {
            $this->outputHeader[$this->rankKeys[$rank['hierarchy']]] = $rank['name'];
        }
    }

    protected function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->synonymyRepository->createQueryBuilder('a')
            ->leftJoin('a.literature', 'lit')
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

        $line[0] = $this->uuidService->getResolvableUri($this->uuidService->getUuid('citation', $taxSynonymy->getLiterature()->getId()));
        $line[1] = $this->literatureRepository->getProtolog($taxSynonymy->getLiterature()->getId());
        $line[2] = 'CC-BY-SA'; // TODO in original $this->settings['classifications_license'];  licence is depending on some app configuration? should be stored with data as it is fixed..?
        $line[3] = date("Y-m-d H:i:s");
        $line[4] = '';
        $line[5] =  $this->uuidService->getResolvableUri($this->uuidService->getUuid('scientific_name', $taxSynonymy->getSpecies()->getId()));
        $line[6] = $taxSynonymy->getSpecies()->getId();
        $line[7] = $taxSynonymy->getClassification()?->getParentTaxonId();
        $line[8] = $taxSynonymy->getActualTaxonId() ?? null;
        $line[9] = ($taxSynonymy->getActualTaxonId()) ? 'synonym' : 'accepted';

        // add parent information
        foreach ($parentTaxSynonymies as $parentTaxSynonymy) {
            /** @var Synonymy $parentTaxSynonymy */
            $line[$this->rankKeys[$parentTaxSynonymy->getSpecies()->getRank()->getHierarchy()]] = $this->taxonService->getScientificName($parentTaxSynonymy->getSpecies()->getId(), $this->hideScientificNameAuthors);
        }

        // add the currently active information
        $line[$this->rankKeys[$taxSynonymy->getSpecies()->getRank()->getHierarchy()]] = $this->taxonService->getScientificName($taxSynonymy->getSpecies()->getId(), $this->hideScientificNameAuthors);
        ksort($line);
        $this->outputBody[] = $line;

        $queryBuilder = $this->getBaseQueryBuilder()
            ->andWhere('a.actualTaxonId = :taxon')
            ->setParameter('reference', $taxSynonymy->getLiterature()->getId())
            ->setParameter('taxon', $taxSynonymy->getSpecies()->getId());

        // fetch all synonyms
        foreach ($queryBuilder->getQuery()->getResult() as $taxSynonymySynonym) {
            $this->exportClassification($parentTaxSynonymies, $taxSynonymySynonym);
        }

        // fetch all children
        $parentTaxSynonymies[] = $taxSynonymy;
        $queryBuilder = $this->getBaseQueryBuilder()
            ->leftJoin('a.classification', 'clas')
            ->andWhere('clas.parentTaxonId = :taxon')
            ->setParameter('reference', $taxSynonymy->getLiterature()->getId())
            ->setParameter('taxon', $taxSynonymy->getSpecies()->getId())
            ->orderBy('clas.sort', 'ASC');

        foreach ($queryBuilder->getQuery()->getResult() as $taxSynonymyChild) {
            $this->exportClassification($parentTaxSynonymies, $taxSynonymyChild);
        }

    }


}
