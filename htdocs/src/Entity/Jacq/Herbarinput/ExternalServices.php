<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use App\Repository\Herbarinput\ExternalServicesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExternalServicesRepository::class)]
#[ORM\Table(name: 'tbl_nom_service', schema: 'herbarinput')]
class ExternalServices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'serviceID')]
    private ?int $id = null;


    #[ORM\Column(name: 'name')]
    private string $name;

    #[ORM\Column(name: 'api_code')]
    private ?string $apiCode;

    #[ORM\Column(name: 'api_url')]
    private ?string $apiUrl;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }


}
