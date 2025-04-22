<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class UuidService
{

    public function __construct(protected EntityManagerInterface $entityManager, protected UuidConfiguration $uuidConfiguration)
    {
    }

    public function getUuid(string $type, int $referenceId): ?string
    {
        $sql = "SELECT uuid FROM uuid_replica WHERE uuid_minter_type = :type  AND internal_id = :id";
        $uuid = $this->entityManager->getConnection()->executeQuery($sql, ['type' => $type, 'id' => $referenceId])->fetchOne();
        if ($uuid) {
            return $uuid;
        } else {
            return $this->getUuidUrl($type, $referenceId);
        }
    }

    /**
     * use input-webservice "uuid" to get the uuid-url for a given id and type
     *
     * TODO - this architecture is not good
     */
    protected function getUuidUrl($type, $id)
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

    public function getTaxonFromUuid(string $uuid): ?int
    {
        $curl = curl_init("https://resolve.jacq.org/resolve.php?uuid=$uuid&type=internal_id");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            curl_close($curl);
            return null;
        } else {
            $taxonID = intval($curl_response);
            curl_close($curl);
            return $taxonID;
        }

    }

    public function getResolvableUri(string $uuid): string
    {
        return $this->uuidConfiguration->prefix . $uuid;
    }

}
