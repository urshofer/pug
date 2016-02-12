<?php

namespace Jade\Compiler;

use Jade\Nodes\Code;

abstract class CodeVisitor extends Visitor
{
    /**
     * @param Nodes\Code $node
     */
    protected function visitCodeConditional(array $matches)
    {
        $code = trim($matches[2], '; ');
        while (($len = strlen($code)) > 1 && ($code[0] == '(' || $code[0] == '{') && ord($code[0]) == ord(substr($code, -1)) - 1) {
            $code = trim(substr($code, 1, $len - 2));
        }

        $index = count($this->buffer) - 1;
        $conditional = '';

        if (isset($this->buffer[$index]) && false !== strpos($this->buffer[$index], $this->createCode('}'))) {
            // the "else" statement needs to be in the php block that closes the if
            $this->buffer[$index] = null;
            $conditional .= '} ';
        }

        $conditional .= '%s';

        if (strlen($code) > 0) {
            $conditional .= '(%s) {';
            if ($matches[1] == 'unless') {
                $conditional = sprintf($conditional, 'if', '!(%s)');
            } else {
                $conditional = sprintf($conditional, $matches[1], '%s');
            }
            return $this->buffer($this->createCode($conditional, $code));
        }

        $conditional .= ' {';
        $conditional = sprintf($conditional, $matches[1]);

        $this->buffer($this->createCode($conditional));
    }

    /**
     * @param Nodes\Code $node
     */
    protected function visitCode(Code $node)
    {
        $code = trim($node->value);

        if ($node->buffer) {
            $pattern = $node->escape ? static::ESCAPED : static::UNESCAPED;
            $this->buffer($this->createCode($pattern, $code));
        } else {
            $php_open = implode('|', $this->phpOpenBlock);

            if (preg_match("/^[[:space:]]*({$php_open})(.*)/", $code, $matches)) {
                $this->visitCodeConditional($matches);
            } else {
                $this->buffer($this->createCode('%s', $code));
            }
        }

        if (isset($node->block)) {
            $this->indents++;
            $this->visit($node->block);
            $this->indents--;

            if (!$node->buffer) {
                $this->buffer($this->createCode('}'));
            }
        }
    }
}
