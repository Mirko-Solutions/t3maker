<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\ConfigRenderTypeInterface;
use Mirko\T3maker\Typo3\TCA\Config\Type\ConfigTypeInterface;

class Config
{

    private ConfigTypeInterface $type;

    private ConfigRenderTypeInterface|null $renderType;

    private int|null $readOnly;

    private int|null $size;
    private array|null $renderTypeConfig;

    public static function createConfig(ConfigTypeInterface $type): Config
    {
        $config = new self();
        $config->setType($type);

        return $config;
    }

    public function __toArray(): array
    {
       return get_object_vars($this);
    }

    /**
     * @return ConfigTypeInterface
     */
    public function getType(): ConfigTypeInterface
    {
        return $this->type;
    }

    /**
     * @param ConfigTypeInterface $type
     */
    public function setType(ConfigTypeInterface $type): void
    {
        $this->type = $type;
    }
}