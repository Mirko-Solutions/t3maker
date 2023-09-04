<?php

declare(strict_types=1);

namespace Mirko\T3maker\Typo3\TCA\Config\RenderType;

use Mirko\T3maker\Typo3\TCA\Config\ReusablePropertiesQuestionFactory;
use ReflectionProperty;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class InlineDefault extends AbstractConfigRenderType implements DefaultRenderTypeInterface
{
    protected array $availableConfigProperties = [
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_FOREIGN_TABLE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_FOREIGN_FIELD,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_FOREIGN_TABLE_FIELD,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MAX_ITEMS,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_SIZE,
        ReusablePropertiesQuestionFactory::CONFIG_PROPERTY_MIN_ITEMS,
    ];

    /**
     * @var array|array[]
     */
    protected array $requiredConfigProperties = [

    ];

    public function askForConfigPresets(SymfonyStyle $io, ReflectionProperty $property): array
    {
        $question = new ConfirmationQuestion('Do you want to apply File Abstraction Layer (FAL) TCA preset?', false);
        $answer = $io->askQuestion($question);

        if ($answer === false) {
            return [];
        }

        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
            Str::asSnakeCase($property->getName()),
            [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                ],
                // custom configuration for displaying fields in the overlay/reference table
                // to use the image overlay palette instead of the basic overlay palette
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;
                            LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '
                            --palette--;
                            LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        );
    }

    public function getExampleConfig(): array
    {
        return [
            'appearance' => [
                'expandAll' => true,
                'showHeader' => true,
            ],
        ];
    }
}
