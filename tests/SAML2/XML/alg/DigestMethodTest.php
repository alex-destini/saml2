<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\alg;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Exception\MissingAttributeException;
use SimpleSAML\SAML2\XML\alg\DigestMethod;
use SimpleSAML\SAML2\Utils;

/**
 * Class \SAML2\XML\alg\DigestMethodTest
 *
 * @covers \SimpleSAML\SAML2\XML\alg\AbstractAlgElement
 * @covers \SimpleSAML\SAML2\XML\alg\DigestMethod
 *
 * @package simplesamlphp/saml2
 */
final class DigestMethodTest extends TestCase
{
    /** @var \DOMDocument */
    private DOMDocument $document;


    /**
     */
    protected function setUp(): void
    {
        $this->document = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/alg_DigestMethod.xml'
        );
    }


    /**
     */
    public function testMarshalling(): void
    {
        $digestMethod = new DigestMethod('http://exampleAlgorithm');

        $this->assertEquals('http://exampleAlgorithm', $digestMethod->getAlgorithm());

        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($digestMethod));
    }


    /**
     */
    public function testUnmarshalling(): void
    {
        $digestMethod = DigestMethod::fromXML($this->document->documentElement);

        $this->assertEquals('http://exampleAlgorithm', $digestMethod->getAlgorithm());
    }


    /**
     */
    public function testUnmarshallingMissingAlgorithmThrowsException(): void
    {
        $document = $this->document->documentElement;
        $document->removeAttribute('Algorithm');

        $this->expectException(MissingAttributeException::class);
        $this->expectExceptionMessage("Missing 'Algorithm' attribute on alg:DigestMethod.");

        DigestMethod::fromXML($document);
    }

    /**
     * Test serialization / unserialization
     */
    public function testSerialization(): void
    {
        $this->assertEquals(
            $this->document->saveXML($this->document->documentElement),
            strval(unserialize(serialize(DigestMethod::fromXML($this->document->documentElement))))
        );
    }
}
