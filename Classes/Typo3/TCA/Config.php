<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA;

use Mirko\T3maker\Typo3\TCA\Config\RenderType\ConfigRenderTypeInterface;
use Mirko\T3maker\Typo3\TCA\Config\Type\ConfigTypeInterface;

class Config
{
    private ConfigTypeInterface $type;

    private ConfigRenderTypeInterface|null $renderType = null;
    private array $renderTypeConfig = [];

    public function __construct(ConfigTypeInterface $type)
    {
        $this->type = $type;
    }

    public function __toArray(): array
    {
        $config = [
            'type' => $this->type::getTypeName(),
        ];

        if ($this->renderType) {
            $config['renderType'] = $this->renderType::getTypeName();
        }

        return array_merge($config, $this->renderTypeConfig);
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

    /**
     * @param ConfigRenderTypeInterface|null $renderType
     */
    public function setRenderType(?ConfigRenderTypeInterface $renderType): void
    {
        $this->renderType = $renderType;
    }

    /**
     * @param array|null $renderTypeConfig
     */
    public function setRenderTypeConfig(?array $renderTypeConfig): void
    {
        $this->renderTypeConfig = $renderTypeConfig;
    }
}
