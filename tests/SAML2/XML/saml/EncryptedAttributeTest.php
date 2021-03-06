<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\saml;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\SAML2\XML\saml\Attribute;
use SimpleSAML\SAML2\XML\saml\AttributeValue;
use SimpleSAML\SAML2\XML\saml\EncryptedAttribute;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XMLSecurity\TestUtils\PEMCertificatesMock;
use SimpleSAML\XMLSecurity\XMLSecurityKey;

/**
 * Class \SAML2\XML\saml\EncryptedAttributeTest
 *
 * @package simplesamlphp/saml2
 * @covers \SimpleSAML\SAML2\XML\saml\EncryptedAttribute
 * @covers \SimpleSAML\SAML2\XML\saml\AbstractSamlElement
 */
final class EncryptedAttributeTest extends TestCase
{
    /** @var \DOMDocument */
    private DOMDocument $document;


    /**
     */
    protected function setUp(): void
    {
        $this->document = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/saml_EncryptedAttribute.xml'
        );
    }


    // marshalling


    /**
     */
    public function testMarshalling(): void
    {
        $pubkey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $pubkey->loadKey(PEMCertificatesMock::getPlainPublicKey(PEMCertificatesMock::PUBLIC_KEY));

        /** @psalm-var \SimpleSAML\SAML2\XML\saml\EncryptedAttribute $encryptedAttribute */
        $encryptedAttribute = EncryptedAttribute::fromUnencryptedElement(
            new Attribute('urn:encrypted:attribute', null, null, [new AttributeValue('very secret data')]),
            $pubkey
        );

        $encryptedData = $encryptedAttribute->getEncryptedData();

        $this->assertEquals('http://www.w3.org/2001/04/xmlenc#Element', $encryptedData->getType());
    }


    // unmarshalling


    /**
     */
    public function testUnmarshalling(): void
    {
        $encryptedAttribute = EncryptedAttribute::fromXML($this->document->documentElement);

        $encryptedData = $encryptedAttribute->getEncryptedData();
        $this->assertEquals('http://www.w3.org/2001/04/xmlenc#Element', $encryptedData->getType());
    }


    /**
     */
    public function testDecryptAttribute(): void
    {
        $encryptedAttribute = EncryptedAttribute::fromXML($this->document->documentElement);

        $privkey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $privkey->loadKey(PEMCertificatesMock::getPlainPrivateKey(PEMCertificatesMock::PRIVATE_KEY));
        /** @psalm-var \SimpleSAML\SAML2\XML\saml\Attribute $decryptedAttribute */
        $decryptedAttribute = $encryptedAttribute->decrypt($privkey, []);

        $this->assertEquals('urn:encrypted:attribute', $decryptedAttribute->getName());
    }


    /**
     * Test serialization / unserialization
     */
    public function testSerialization(): void
    {
        $this->assertEquals(
            $this->document->saveXML($this->document->documentElement),
            strval(unserialize(serialize(EncryptedAttribute::fromXML($this->document->documentElement))))
        );
    }
}
