<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Safe\Exceptions\LibxmlException;
use Safe\Exceptions\SimplexmlException;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\libxml_get_last_error;
use function Safe\simplexml_load_string;
use VysokeSkoly\UtilsBundle\Exception\DecodingException;

class XmlHelper
{
    public static function getXmlAttributeValue(\SimpleXMLElement $xml, string $attributeName): ?string
    {
        return isset($xml[$attributeName]) ? (string) $xml[$attributeName] : null;
    }

    public static function convertXmlToArray(\SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true) ?? [];
    }

    /**
     * Safely converts string to SimpleXML
     */
    public static function stringToXml(string $data): \SimpleXMLElement
    {
        $errorMessage = null;

        // Save previous value
        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $xml = simplexml_load_string($data);
        } catch (SimplexmlException $e) {
            throw new DecodingException('Unable to parse response body into XML: ' . $e->getMessage());
        }

        try {
            if (($error = libxml_get_last_error()) !== null) {
                $errorMessage = $error->message;
            }
        } catch (LibxmlException $e) {
            // There is no libxml error
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if ($errorMessage) {
            throw new DecodingException('Unable to parse response body into XML: ' . $errorMessage);
        }

        return $xml;
    }

    /**
     * Sanitize XML from invalid XML characters defined by XML specification.
     * Replace invalid characters with space.
     *
     * @see http://www.w3.org/TR/xml/#charsets
     */
    public static function sanitizeXml(string $xml): string
    {
        $output = '';
        $xmlLength = strlen($xml);  // intentional use of strlen instead of mb_strlen

        for ($i = 0; $i < $xmlLength; $i++) {
            $current = ord($xml[$i]);
            if (($current == 0x9) || ($current == 0xA) || ($current == 0xD)
                || (($current >= 0x20) && ($current <= 0xD7FF))
                || (($current >= 0xE000) && ($current <= 0xFFFD))
                || (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $output .= chr($current);
            } else { // replace characters not allowed in XML with space
                $output .= ' ';
            }
        }

        return $output;
    }
}
