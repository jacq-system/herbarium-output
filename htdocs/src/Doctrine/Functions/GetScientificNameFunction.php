<?php declare(strict_types=1);

namespace App\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class GetScientificNameFunction extends FunctionNode
{
    public $taxonId = null;
    public $avoidHybridFormula = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->taxonId = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->avoidHybridFormula = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'herbar_view.GetScientificName(' .
            $this->taxonId->dispatch($sqlWalker) . ', ' .
            $this->avoidHybridFormula->dispatch($sqlWalker) .
            ')';
    }
}
