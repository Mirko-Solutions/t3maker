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

use Mirko\T3maker\Utility\StringUtility;

/**
 * @internal
 */
final class RelationManyToMany extends BaseCollectionRelation
{
    public function getTargetSetterMethodName(): string
    {
        return 'add' . StringUtility::asCamelCase(
            StringUtility::pluralCamelCaseToSingular($this->getTargetPropertyName())
        );
    }

    public function getTargetRemoverMethodName(): string
    {
        return 'remove' . StringUtility::asCamelCase(
            StringUtility::pluralCamelCaseToSingular($this->getTargetPropertyName())
        );
    }
}
