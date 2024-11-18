<?php declare(strict_types=1);

namespace App\Enum;

enum CoreObjectsEnum: string
{
    case Names = 'names';
    case Citations = 'citations';
    case Names_citations = 'names_citations';
    case Specimens = 'specimens';
    case Type_specimens = 'type_specimens';
    case Names_type_specimens = 'names_type_specimens';
    case Types_name = 'types_name';
    case Synonyms = 'synonyms';
    //TODO classifications was missing in OA definition but present in code
    case Classifications = 'classifications';

}
