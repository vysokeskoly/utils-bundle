<?php declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(
        Option::SKIP,
        [
            MbStrFunctionsFixer::class => [
                // intentional use of strlen instead of mb_strlen
                'src/Service/XmlHelper.php',
                // intentional use of strpos and strlen because of substr_replace is not multi-byte
                'src/Service/HtmlHelper.php',
            ],
        ],
    );

    $containerConfigurator->import(__DIR__ . '/tools/coding-standards/vendor/lmc/coding-standard/ecs.php');
    $containerConfigurator->import(__DIR__ . '/tools/coding-standards/vendor/lmc/coding-standard/ecs-8.1.php');
};
