<?php

namespace phpMyFAQ\Core;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Json::class)]
class JsonTest extends TestCase
{
    public function testDecodeAssocReturnsNestedArrays(): void
    {
        $this->assertSame(['a' => ['b' => 1]], Json::decodeAssoc('{"a":{"b":1}}'));
        $this->assertSame([1, 2], Json::decodeAssoc('[1,2]'));
    }

    public function testDecodeAssocReturnsNullForScalarsAndMalformedJson(): void
    {
        $this->assertNull(Json::decodeAssoc('"text"'));
        $this->assertNull(Json::decodeAssoc('42'));
        $this->assertNull(Json::decodeAssoc('{not json'));
        $this->assertNull(Json::decodeAssoc(''));
    }

    public function testDecodeAssocThrowsWhenAskedTo(): void
    {
        $this->expectException(JsonException::class);
        Json::decodeAssoc('{not json', JSON_THROW_ON_ERROR);
    }

    public function testDecodeObjectReturnsStdClassOnlyForObjects(): void
    {
        $object = Json::decodeObject('{"name":"faq","nested":{"id":1}}');

        $this->assertInstanceOf(stdClass::class, $object);
        $this->assertSame('faq', $object->name);
        $this->assertInstanceOf(stdClass::class, $object->nested);
        $this->assertNull(Json::decodeObject('[1,2]'));
        $this->assertNull(Json::decodeObject('null'));
        $this->assertNull(Json::decodeObject('{broken'));
    }

    public function testDecodeObjectThrowsWhenAskedTo(): void
    {
        $this->expectException(JsonException::class);
        Json::decodeObject('{broken', JSON_THROW_ON_ERROR);
    }

    public function testDecodeListKeepsNestedObjects(): void
    {
        $list = Json::decodeList('[{"id":1},{"id":2}]');

        $this->assertIsArray($list);
        $this->assertCount(2, $list);
        $this->assertInstanceOf(stdClass::class, $list[0]);
        $this->assertSame(2, $list[1]->id);
        $this->assertNull(Json::decodeList('{"id":1}'));
        $this->assertNull(Json::decodeList('oops'));
    }

    public function testDepthIsHonoured(): void
    {
        $this->assertNull(Json::decodeAssoc('[[[1]]]', 0, 2));
        $this->assertSame([[[1]]], Json::decodeAssoc('[[[1]]]', 0, 4));
    }
}
