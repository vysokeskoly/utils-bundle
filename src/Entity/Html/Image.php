<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Entity\Html;

use function Safe\ksort;

class Image
{
    public const REQUIRED_KEYS = ['src', 'alt'];

    private array $parameters;
    private bool $isValid = true;

    public static function getMimeType(string $extension): string
    {
        [$extension] = explode(' ', $extension, 2);

        $mimetypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
        ];

        return $mimetypes[$extension] ?? 'image/*';  // https://superuser.com/questions/979135/is-there-a-generic-mime-type-for-all-image-files
    }

    public function __construct(array $parameters)
    {
        $this->setParameters($parameters);
    }

    private function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;

        foreach (self::REQUIRED_KEYS as $key) {
            if (!array_key_exists($key, $this->parameters)) {
                $this->parameters[$key] = '';
            }
        }

        ksort($this->parameters);
    }

    public function setAlt(string $alt): void
    {
        $parameters = $this->getParameters();
        $parameters['alt'] = $alt;

        $this->setParameters($parameters);
    }

    public function getAlt(): string
    {
        return $this->parameters['alt'];
    }

    public function setSrc(string $src): void
    {
        $parameters = $this->getParameters();
        $parameters['src'] = $src;

        $this->setParameters($parameters);
    }

    public function getSrc(): string
    {
        return $this->parameters['src'];
    }

    public function getParameters(array $excluded = []): array
    {
        $parameters = $this->parameters;

        foreach ($excluded as $excludeKey) {
            if (array_key_exists($excludeKey, $this->parameters)) {
                unset($parameters[$excludeKey]);
            }
        }

        return $parameters;
    }

    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }
}
