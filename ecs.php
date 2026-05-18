<?php

declare(strict_types=1);

use AlmaOss\CodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$doctrineCacheBridge = [
    'src/FrontBundle/Adapter/DoctrineCacheBridge.php',
];

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withSets([
        SetList::ALMACAREER,
    ])
    ->withSkip([
        MbStrFunctionsFixer::class => [
            // intentional use of strlen instead of mb_strlen
            'src/Service/XmlHelper.php',
            // intentional use of strpos and strlen because of substr_replace is not multi-byte
            'src/Service/HtmlHelper.php',

            // using trim, which is not available in 8.1
            'src/Service/StringUtils.php',
            'src/Service/LinkPlaceholderTransformer.php',
            'src/Service/Url.php',
        ],
    ]);
