<?php declare(strict_types = 1);

namespace App\Entity\Jacq\Herbarinput;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'tbl_specimens_voucher', schema: 'herbarinput')]
class SpecimenVoucherType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'voucherID')]
    private ?int $id = null;


    #[ORM\Column(name: 'voucher')]
    private ?string $name;

    public function getName(): ?string
    {
        return $this->name;
    }


}
