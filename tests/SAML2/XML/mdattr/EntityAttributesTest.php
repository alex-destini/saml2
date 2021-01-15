<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\mdattr;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\SAML2\Constants;
use SimpleSAML\SAML2\XML\saml\Assertion;
use SimpleSAML\SAML2\XML\saml\Attribute;
use SimpleSAML\SAML2\XML\saml\AttributeStatement;
use SimpleSAML\SAML2\XML\saml\AttributeValue;
use SimpleSAML\SAML2\XML\saml\AuthnContext;
use SimpleSAML\SAML2\XML\saml\AuthnContextClassRef;
use SimpleSAML\SAML2\XML\saml\AuthnContextDeclRef;
use SimpleSAML\SAML2\XML\saml\AudienceRestriction;
use SimpleSAML\SAML2\XML\saml\Conditions;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\mdattr\EntityAttributes;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Utils as XMLUtils;
use SimpleSAML\XMLSecurity\TestUtils\PEMCertificatesMock;
use SimpleSAML\XMLSecurity\XMLSecurityKey;

/**
 * Class \SAML2\XML\mdattr\EntityAttributesTest
 *
 * @covers \SimpleSAML\SAML2\XML\mdattr\EntityAttributes
 * @covers \SimpleSAML\SAML2\XML\mdattr\AbstractMdattrElement
 * @package simplesamlphp/saml2
 */

final class EntityAttributesTest extends TestCase
{
    /** @var \DOMDocument */
    private DOMDocument $document;


    /**
     */
    public function setUp(): void
    {
        $this->document = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/mdattr_EntityAttributes.xml'
        );
    }


    /**
     */
    public function testMarshalling(): void
    {
        $attribute1 = new Attribute(
            'attrib1',
            Constants::NAMEFORMAT_URI,
            null,
            [
                new AttributeValue('is'),
                new AttributeValue('really'),
                new AttributeValue('cool'),
            ]
        );

        // Create an Issuer
        $issuer = new Issuer('testIssuer');

        // Create the conditions
        $conditions = new Conditions(
            null,
            null,
            [],
            [new AudienceRestriction(['audience1', 'audience2'])]
        );

        // Create the statements
        $attrStatement = new AttributeStatement(
            [
                new Attribute(
                    'urn:mace:dir:attribute-def:uid',
                    Constants::NAMEFORMAT_URI,
                    null,
                    [
                        new AttributeValue('student2')
                    ]
                ),
                new Attribute(
                    'urn:mace:terena.org:attribute-def:schacHomeOrganization',
                    Constants::NAMEFORMAT_URI,
                    null,
                    [
                        new AttributeValue('university.example.org'),
                        new AttributeValue('bbb.cc')
                    ]
                ),
                new Attribute(
                    'urn:schac:attribute-def:schacPersonalUniqueCode',
                    Constants::NAMEFORMAT_URI,
                    null,
                    [
                        new AttributeValue('urn:schac:personalUniqueCode:nl:local:uvt.nl:memberid:524020'),
                        new AttributeValue('urn:schac:personalUniqueCode:nl:local:surfnet.nl:studentid:12345')
                    ]
                ),
                new Attribute(
                    'urn:mace:dir:attribute-def:eduPersonAffiliation',
                    Constants::NAMEFORMAT_URI,
                    null,
                    [
                        new AttributeValue('member'),
                        new AttributeValue('student')
                    ]
                )
            ]
        );

        // Create an assertion
        $unsignedAssertion = new Assertion($issuer, null, 1610743797, null, $conditions, [$attrStatement]);

        // Sign the assertion
        $privateKey = PEMCertificatesMock::getPrivateKey(XMLSecurityKey::RSA_SHA256, PEMCertificatesMock::PRIVATE_KEY);
        $unsignedAssertion->setSigningKey($privateKey);
        $unsignedAssertion->setCertificates([PEMCertificatesMock::getPlainPublicKey(PEMCertificatesMock::PUBLIC_KEY)]);
        $signedAssertion = Assertion::fromXML($unsignedAssertion->toXML());

        $attribute2 = new Attribute(
            'foo',
            'urn:simplesamlphp:v1:simplesamlphp',
            null,
            [
                new AttributeValue('is'),
                new AttributeValue('really'),
                new AttributeValue('cool')
            ]
        );

        $entityAttributes = new EntityAttributes([$attribute1]);
        $entityAttributes->addChild($signedAssertion);
        $entityAttributes->addChild($attribute2);

        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($entityAttributes));
    }


    /**
     */
    public function testUnmarshalling(): void
    {
        $entityAttributes = EntityAttributes::fromXML($this->document->documentElement);
        $this->assertCount(4, $entityAttributes->getChildren());

        $this->assertInstanceOf(Attribute::class, $entityAttributes->getChildren()[0]);
        $this->assertInstanceOf(Assertion::class, $entityAttributes->getChildren()[2]);
        $this->assertInstanceOf(Attribute::class, $entityAttributes->getChildren()[3]);

        $this->assertEquals('attrib1', $entityAttributes->getChildren()[0]->getName());
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:attrname-format:uri', $entityAttributes->getChildren()[0]->getNameFormat());
        $this->assertCount(1, $entityAttributes->getChildren()[0]->getAttributeValues());

        $this->assertEquals('Assertion', $entityAttributes->getChildren()[1]->getLocalName());
        $this->assertEquals('2021-01-15T20:52:26.000Z', $entityAttributes->getChildren()[1]->getXML()->getAttribute('IssueInstant'));

        $this->assertEquals('urn:simplesamlphp:v1:simplesamlphp', $entityAttributes->getChildren()[2]->getName());
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:attrname-format:uri', $entityAttributes->getChildren()[2]->getNameFormat());
        $this->assertCount(3, $entityAttributes->getChildren()[2]->getAttributeValues());
    }


    /**
     * Test serialization / unserialization
     */
    public function testSerialization(): void
    {
        $this->assertEquals(
            $this->document->saveXML($this->document->documentElement),
            strval(unserialize(serialize(EntityAttributes::fromXML($this->document->documentElement))))
        );
    }
}
