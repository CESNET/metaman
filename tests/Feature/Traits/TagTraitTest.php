<?php

namespace Tests\Feature\Traits;

use App\Traits\EntitiesXML\TagTrait;
use DOMDocument;
use DOMException;
use Tests\TestCase;

class TagTraitTest extends TestCase
{
    use TagTrait;

    public function test_throws_exception_if_not_dom_node()
    {

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Argument must be an instance of \DOMNode');
        $this->hasChildElements((object) 'not a node');
    }

    /**
     * @throws DOMException
     */
    public function test_returns_true_when_element_child_exists()
    {
        $dom = new DOMDocument;
        $parent = $dom->createElement('parent');
        $child = $dom->createElement('child');
        $parent->appendChild($child);

        $this->assertTrue($this->hasChildElements($parent));
    }

    public function test_returns_false_when_only_text_child()
    {
        $dom = new \DOMDocument;
        $parent = $dom->createElement('parent');
        $text = $dom->createTextNode('just text');
        $parent->appendChild($text);

        $this->assertFalse($this->hasChildElements($parent));
    }

    public function test_returns_true_when_xpath_matches()
    {
        $xml = <<<'XML'
<root><item>1</item></root>
XML;

        $result = $this->hasXpathQueryInDocument($xml, '/root/item');
        $this->assertTrue($result);
    }

    public function test_returns_false_when_xpath_does_not_match()
    {
        $xml = <<<'XML'
<root><item>1</item></root>
XML;

        $result = $this->hasXpathQueryInDocument($xml, '/root/other');
        $this->assertFalse($result);
    }

    public function test_throws_when_xpath_query_is_invalid()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error executing XPath query');

        $xml = <<<'XML'
<root><item>1</item></root>
XML;

        $this->hasXpathQueryInDocument($xml, '/root/item[');
    }

    public function test_throws_when_xml_is_invalid()
    {
        $xml = <<<'XML'
<root><item>1</root>
XML;
        $this->assertFalse($this->hasXpathQueryInDocument($xml, '/root/item'));
    }

    public function test_deletes_tag_from_parent()
    {
        $dom = new \DOMDocument;
        $parent = $dom->createElement('parent');
        $child = $dom->createElement('child');
        $parent->appendChild($child);

        $this->assertSame(1, $parent->childNodes->length);

        $this->deleteTag($child);

        $this->assertSame(0, $parent->childNodes->length);
    }

    public function test_throws_exception_when_not_dom_node()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument must be an instance of \DOMNode');

        $this->deleteTag((object) 'not a node');
    }

    public function test_does_nothing_when_no_parent_node()
    {
        $dom = new \DOMDocument;
        $element = $dom->createElement('lonely');

        $this->deleteTag($element);

        $this->assertNull($element->parentNode);
    }

    public function test_throws_exception_if_not_dom_node_delete_no_chilled_tag()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument must be an instance of \DOMNode');

        $this->deleteNoChilledTag((object) 'not a node');
    }

    public function test_deletes_tag_if_no_child_elements()
    {
        $dom = new \DOMDocument;
        $parent = $dom->createElement('parent');
        $child = $dom->createElement('emptyChild');
        $parent->appendChild($child);
        $dom->appendChild($parent);

        $this->assertSame(1, $parent->childNodes->length);

        $this->deleteNoChilledTag($child);

        $this->assertSame(0, $parent->childNodes->length);
    }

    public function test_does_not_delete_tag_if_has_child_elements()
    {
        $dom = new \DOMDocument;
        $parent = $dom->createElement('parent');
        $child = $dom->createElement('childWithContent');
        $grandChild = $dom->createElement('sub');
        $child->appendChild($grandChild);
        $parent->appendChild($child);
        $dom->appendChild($parent);

        $this->assertSame(1, $parent->childNodes->length);

        $this->deleteNoChilledTag($child);

        $this->assertSame(1, $parent->childNodes->length);
    }

    public function test_throws_when_xpath_query_is_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('XPath query string cannot be empty');

        $dom = $this->createDOM('<root/>');
        $xpath = $this->createXPath($dom);
        $this->deleteAllTags('', $xpath);
    }

    public function test_throws_when_xpath_query_fails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error executing XPath query');
        $dom = $this->createDOM('<root><tag/></root>');
        $xpath = $this->createXPath($dom);
        $this->deleteAllTags('//*[', $xpath);
    }

    public function test_deletes_matching_tags_and_cleans_parents()
    {
        $xml = <<<'XML'
<root>
    <wrapper>
        <emptyMe>
            <removeMe/>
        </emptyMe>
    </wrapper>
</root>
XML;
        $dom = $this->createDOM($xml);
        $xpath = $this->createXPath($dom);

        $removeMeList = $xpath->query('//removeMe');
        $this->assertSame(1, $removeMeList->length);

        $this->deleteAllTags('//removeMe', $xpath);

        $removeMeListAfter = $xpath->query('//removeMe');
        $this->assertSame(0, $removeMeListAfter->length);

        $emptyMeList = $xpath->query('//emptyMe');
        $this->assertSame(0, $emptyMeList->length);

        $wrapperList = $xpath->query('//wrapper');

        $this->assertSame(0, $wrapperList->length);
    }
}
