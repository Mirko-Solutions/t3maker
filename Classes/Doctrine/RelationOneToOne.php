<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mirko\T3maker\Doctrine;

use Symfony\Bundle\MakerBundle\Str;

/**
 * @internal
 */
final class RelationOneToOne extends BaseRelation
{
    public function getTargetGetterMethodName(): string
    {
        return 'get' . Str::asCamelCase($this->getTargetPropertyName());
    }

    public function getTargetSetterMethodName(): string
    {
        return 'set' . Str::asCamelCase($this->getTargetPropertyName());
    }
}
