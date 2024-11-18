<?php declare(strict_types=1);

namespace App\Service\Rest;


use Doctrine\ORM\EntityManagerInterface;

class HerbNumberScan
{

    protected string $HerbNummer = '';
    protected int $source_id = 0;

    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * find a new HerbNummer within a scantext which is either a stable-ID or a barcode-text
     * cut off any trailing non-numeric characters beforehand
     *
     * @param string $searchText scantext
     */

    public function initialize(string $searchText): static
    {

        $posProt = strpos($searchText, '://');
        if ($posProt !== false) {
            $searchText = substr($searchText, $posProt + 3);
            $isStableID = 1;
        } else {
            $isStableID = 0;
        }

        $sql = "SELECT id, source_id, collectionID, `text`, HerbNummerConstruct, LENGTH(`text`) AS match_length
                             FROM scanHerbNummer
                             WHERE `text` = SUBSTRING(:searchText, 1, LENGTH(`text`))
                              AND isStableID = :isStableID
                             ORDER BY match_length DESC";
        $row = $this->entityManager->getConnection()->executeQuery($sql, ["isStableID"=>$isStableID, "searchText"=>$searchText])->fetchAssociative();

        if (empty($row)) {
            $this->HerbNummer = $searchText;
            $this->source_id = 0;
        } else {
            preg_match("/^\d+/", substr($searchText, $row['match_length']), $matches);  // cut off any trailing non-numeric characters
            if (empty($matches)) {
                $this->HerbNummer = $searchText;
                $this->source_id = 0;
            } else {
                $remainingText = $matches[0];
                $constructor = $this->findConstructor($row['HerbNummerConstruct'], strlen($remainingText));
                $this->HerbNummer = $this->generateHerbNumber($remainingText, $constructor);
                $this->source_id = $row['source_id'];
            }
        }
        return $this;
    }

    /**
     * Analyse the column HerbNummerConstruct from the database to get the actual constructor
     * special characters:
     * '|' ... several constructors may be seperated with it. If present, length constraints of each constructor must be given
     * '/' ... seperates a length constraint from the connected constructor. Constructor will be used, when the remaining text of the searchstring has this length
     * '*' ... stands for an arbitrary length. Must be present, when more than one constructor is given
     *
     * @param string $HerbNummerConstruct constructor, read directly from database
     * @param int $remainingTextLen length of the remaining text, which will be the source of the function generateHerbNummer
     * @return string the constructor to use
     */
    protected function findConstructor(string $HerbNummerConstruct, int $remainingTextLen): string
    {
        if (strpos($HerbNummerConstruct, '|') !== false) {
            $constructors_raw = explode('|', $HerbNummerConstruct);
            $constructor_universal = $constructor = '';
            foreach ($constructors_raw as $item) {
                $parts = explode('/', $item, 2);
                if ($parts[0] == '*') {
                    $constructor_universal = $parts[1];
                } else {
                    if ($remainingTextLen == $parts[0]) {
                        $constructor = $parts[1];
                    }
                }
            }
            if (empty($constructor)) {
                $constructor = $constructor_universal;
            }
        } else {
            $constructor = $HerbNummerConstruct;
        }

        return $constructor;
    }

    /**
     * generate a HerbNummer from a source according to the given constructor
     * every character of the constructor is used as it is. Exceptions are:
     * '%' ... the following digit gives the number of characters to get from source
     * '*' ... use all of the remaining characters of source. Must be the last character of the constructor
     *
     * @param string $source source of new HerbNummer
     * @param string $constructor construction instructions
     * @return string the final HerbNummer
     */
    protected function generateHerbNumber(string $source, string $constructor): string
    {
        $target = '';
        $sptr = 0;
        for ($cptr = 0; $cptr < strlen($constructor); $cptr++) {
            if ($constructor[$cptr] == '%') {
                $target .= substr($source, $sptr, (int) $constructor[++$cptr]);
                $sptr += (int)$constructor[$cptr];
            } elseif ($constructor[$cptr] == '*') {
                $target .= substr($source, $sptr);
                break;
            } else {
                $target .= $constructor[$cptr];
            }
        }

        return trim($target);
    }

    public function getHerbNumber(): string
    {
        return $this->HerbNummer;
    }

    public function getSourceId(): int
    {
        return $this->source_id;
    }

}
